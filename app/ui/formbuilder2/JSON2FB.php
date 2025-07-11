<?php

namespace App\UI\FormBuilder2;

use App\Constants\AConstant;
use App\Constants\Container\ProcessInstanceOperations;
use App\Core\Container;
use App\Core\Router;
use App\Exceptions\AException;
use App\Exceptions\GeneralException;
use App\Helpers\UnitConversionHelper;
use App\Repositories\Container\ProcessMetadataRepository;
use App\Repositories\TransactionLogRepository;
use App\UI\FormBuilder2\FormState\FormStateListHelper;

/**
 * JSON2FB is a helper-type class that helps with converting JSON form definition file to FormBuilder elements.
 * 
 * @author Lukas Velek
 */
class JSON2FB {
    private const LABEL = 'label';
    private const TEXT = 'text';
    private const PASSWORD = 'password';
    private const NUMBER = 'number';
    private const SELECT = 'select';
    private const CHECKBOX = 'checkbox';
    private const DATE = 'date';
    private const DATETIME = 'datetime';
    private const TIME = 'time';
    private const FILE = 'file';
    private const EMAIL = 'email';
    private const TEXTAREA = 'textarea';
    private const SUBMIT = 'submit';
    private const BUTTON = 'button';
    private const USER_SELECT = 'userSelect';
    private const USER_SELECT_SEARCH = 'userSelectSearch';
    private const SELECT_SEARCH = 'selectSearch';
    private const DOCUMENT_SELECT_SEARCH = 'documentSelectSearch';
    private const PROCESS_SELECT_SEARCH = 'processSelectSearch';

    private const ACCEPT_BUTTON = 'acceptButton';
    private const REJECT_BUTTON = 'rejectButton';
    private const CANCEL_BUTTON = 'cancelButton';
    private const FINISH_BUTTON = 'finishButton';
    private const ARCHIVE_BUTTON = 'archiveButton';

    private FormBuilder2 $form;
    private array $json;
    private ?string $containerId;
    
    private array $skipAttributes;
    private array $skipElementAttributes;
    private array $formData;
    private array $customUrlParams;
    private bool $callAfterSubmitReducer = false;
    private array $skipElementTypes = [];
    private array $formHandleButtonsParams = [];
    private bool $isEditor = false;
    private bool $checkHandleButtons = false;
    private bool $checkNoHandleButtons = false;
    private ?string $processId = null;

    private ?Container $container;
    
    /**
     * Class constructor
     * 
     * @param FormBuilder2 $form FormBuilder2 instance
     * @param array $json JSON with form data
     * @param ?string $containerId Container ID
     */
    public function __construct(FormBuilder2 $form, array $json, ?string $containerId) {
        $this->form = $form;
        $this->json = $json;
        $this->containerId = $containerId;

        $this->skipAttributes = [];
        $this->skipElementAttributes = [];
        $this->formData = [];
        $this->customUrlParams = [];

        if($this->containerId !== null) {
            $this->container = new Container($this->form->app, $this->containerId);
        } else {
            $this->container = null;
        }
    }

    /**
     * Sets process ID
     * 
     * @param string $processId Process ID
     */
    public function setProcessId(string $processId) {
        $this->processId = $processId;
    }

    /**
     * Sets if the form is rendered for form editor
     * 
     * @param bool $editor Is editor?
     */
    public function setEditor(bool $editor = true) {
        $this->isEditor = $editor;
    }

    /**
     * Checks for form handling buttons
     */
    public function checkForHandleButtons() {
        $this->checkHandleButtons = true;
    }

    /**
     * Checks for no form handling buttons
     */
    public function checkForNoHandleButtons() {
        $this->checkNoHandleButtons = true;
    }

    /**
     * Sets custom URL parameters for search selects
     * 
     * @param array $customUrlParams Custom URL parameters
     */
    public function setCustomUrlParams(array $customUrlParams) {
        $this->customUrlParams = $customUrlParams;
    }

    /**
     * Adds attributes to skip for given element
     * 
     * @param string $elementType Element type
     * @param string ...$attributes Attributes to skip
     */
    public function addSkipElementAttributes(string $elementType, string ...$attributes) {
        $this->skipElementAttributes[$elementType] = $attributes;
    }

    /**
     * Sets attributes to skip
     * 
     * @param array $skipAttributes Attributes to skip
     */
    public function setSkipAttributes(array $skipAttributes) {
        $this->skipAttributes = $skipAttributes;
    }

    /**
     * Adds submit button to the form
     * 
     * @param string $text Submit button text
     */
    public function addSubmitButton(string $text) {
        $this->json['elements'][] = [
            'type' => self::SUBMIT,
            'name' => 'btn_submit',
            'text' => $text
        ];
    }

    /**
     * Processes the form
     */
    private function process() {
        $rootMandatoryAttributes = [
            'name',
            'elements',
            'action'
        ];

        foreach($rootMandatoryAttributes as $attr) {
            if(in_array($attr, $this->skipAttributes)) continue;

            if(!array_key_exists($attr, $this->json)) {
                throw new GeneralException('Attribute \'' . $attr . '\' is not set in the form JSON.');
            }
        }

        $this->form->setName($this->json['name']);
        $this->form->setComponentName('form_' . $this->json['name']);

        if(in_array('action', $this->skipAttributes) && array_key_exists('action', $this->json)) {
            $this->form->setAction($this->json['action']);
        }

        $this->processElements($this->json['elements']);

        if(array_key_exists('reducer', $this->json)) {
            $reducer = $this->json['reducer'];

            if(str_ends_with($reducer, '.php')) {
                $reducer = substr($reducer, 0, -4);
            }

            if(class_exists($reducer)) {
                /**
                 * @var \App\UI\FormBuilder2\ABaseFormReducer $reducerObj
                 */
                $reducerObj = new $reducer($this->form->app, $this->form->httpRequest);
                $reducerObj->setContainerId($this->containerId);

                $this->form->reducer = $reducerObj;
                $this->form->setCallReducerOnChange();

                if($this->callAfterSubmitReducer && !empty($this->formData)) {
                    $stateList = $this->form->getStateList();

                    $fslh = new FormStateListHelper();
                    $fslh->applyProcessInstanceDataToFormStateList($stateList, $this->formData);
                    $fslh->setElementsInFormStateListReadonly($stateList);

                    $this->form->reducer->applyAfterSubmitOnOpenReducer($stateList);
                    $this->form->applyStateList($stateList);
                }
            }
        }
    }

    /**
     * Processes all elements
     * 
     * @param array $elements Elements
     */
    private function processElements(array $elements) {
        $elementMandatoryAttributes = [
            'name',
            'type'
        ];

        $allElementTypes = [];
        foreach($elements as $element) {
            $allElementTypes[] = $element['type'];
        }

        $getHandlersInForm = function() use ($allElementTypes) {
            return array_intersect([
                self::ACCEPT_BUTTON,
                self::ARCHIVE_BUTTON,
                self::CANCEL_BUTTON,
                self::FINISH_BUTTON,
                self::REJECT_BUTTON,
                self::SUBMIT
            ], $allElementTypes);
        };

        if($this->checkHandleButtons && !$this->isEditor) {
            if(empty($getHandlersInForm())) {
                throw new GeneralException('No handle button is defined.');
            }
        }

        if($this->checkNoHandleButtons && !$this->isEditor) {
            if(!empty($getHandlersInForm())) {
                throw new GeneralException('Handle button is defined.');
            }
        }

        foreach($elements as $element) {
            foreach($elementMandatoryAttributes as $attr) {
                if(in_array($element['type'], $this->skipElementAttributes) && in_array($attr, $this->skipElementAttributes[$element['name']])) continue;

                if(!array_key_exists($attr, $element)) {
                    throw new GeneralException('Attribute \'' . $attr . '\' is not set in the form JSON.');
                }
            }

            $name = $element['name'];
            $label = $name;
            if(array_key_exists('label', $element)) {
                $label = $element['label'];
            }

            $elem = null;

            if(in_array($element['type'], $this->skipElementTypes)) continue;

            if(empty($this->formData)) {
                // ELEMENT (INSTANCE) CREATION
                switch($element['type']) {
                    case self::TEXT:
                        $elem = $this->form->addTextInput($name, $label);
                        break;
                    
                    case self::PASSWORD:
                        $elem = $this->form->addPasswordInput($name, $label);
                        break;

                    case self::NUMBER:
                        $elem = $this->form->addNumberInput($name, $label);
                        break;

                    case self::SELECT:
                        $elem = $this->form->addSelect($name, $label);
                        break;

                    case self::CHECKBOX:
                        $elem = $this->form->addCheckboxInput($name, $label);
                        break;

                    case self::DATE:
                        $elem = $this->form->addDateInput($name, $label);
                        break;

                    case self::DATETIME:
                        $elem = $this->form->addDateTimeInput($name, $label);
                        break;

                    case self::EMAIL:
                        $elem = $this->form->addEmailInput($name, $label);
                        break;

                    case self::FILE:
                        $elem = $this->form->addFileInput($name, $label);
                        break;

                    case self::TEXTAREA:
                        $elem = $this->form->addTextArea($name, $label);
                        break;

                    case self::TIME:
                        $elem = $this->form->addTimeInput($name, $label);
                        break;

                    case self::SUBMIT:
                        if(!array_key_exists('text', $element)) {
                            $this->throwExceptionForUnsetAttribute('text', $element['type']);
                        } else {
                            $elem = $this->form->addSubmit($element['text'], 'btn_submit');
                        }
                        break;

                    case self::BUTTON:
                        if(!array_key_exists('text', $element)) {
                            $this->throwExceptionForUnsetAttribute('text', $element['type']);
                        } else {
                            $elem = $this->form->addButton($element['text']);
                        }
                        break;

                    case self::ACCEPT_BUTTON:
                        $elem = $this->form->addButton(ProcessInstanceOperations::toString(ProcessInstanceOperations::ACCEPT));
                        $url = Router::generateUrl(array_merge($this->formHandleButtonsParams, ['operation' => 'accept']));
                        if(!$this->isEditor) {
                            $elem->setOnClick('location.href=\'' . $url . '\';');
                        }
                        break;

                    case self::CANCEL_BUTTON:
                        $elem = $this->form->addButton(ProcessInstanceOperations::toString(ProcessInstanceOperations::CANCEL));
                        $url = Router::generateUrl(array_merge($this->formHandleButtonsParams, ['operation' => 'cancel']));
                        if(!$this->isEditor) {
                            $elem->setOnClick('location.href=\'' . $url . '\';');
                        }
                        break;

                    case self::FINISH_BUTTON:
                        $elem = $this->form->addButton(ProcessInstanceOperations::toString(ProcessInstanceOperations::FINISH));
                        $url = Router::generateUrl(array_merge($this->formHandleButtonsParams, ['operation' => 'finish']));
                        if(!$this->isEditor) {
                            $elem->setOnClick('location.href=\'' . $url . '\';');
                        }
                        break;

                    case self::ARCHIVE_BUTTON:
                        $elem = $this->form->addButton(ProcessInstanceOperations::toString(ProcessInstanceOperations::ARCHIVE));
                        $url = Router::generateUrl(array_merge($this->formHandleButtonsParams, ['operation' => 'archive']));
                        if(!$this->isEditor) {
                            $elem->setOnClick('location.href=\'' . $url . '\';');
                        }
                        break;

                    case self::REJECT_BUTTON:
                        $elem = $this->form->addButton(ProcessInstanceOperations::toString(ProcessInstanceOperations::REJECT));
                        $url = Router::generateUrl(array_merge($this->formHandleButtonsParams, ['operation' => 'reject']));
                        if(!$this->isEditor) {
                            $elem->setOnClick('location.href=\'' . $url . '\';');
                        }
                        break;

                    case self::LABEL:
                        if(!array_key_exists('text', $element)) {
                            $this->throwExceptionForUnsetAttribute('text', $element['type']);
                        } else {
                            $elem = $this->form->addLabel($name, $element['text']);
                        }
                        break;

                    case self::USER_SELECT:
                        if(!array_key_exists('containerId', $element) && (!array_key_exists($element['type'], $this->skipElementAttributes) || (array_key_exists($element['type'], $this->skipElementAttributes) && !in_array('containerId', $this->skipElementAttributes[$element['type']])))) {
                            $this->throwExceptionForUnsetAttribute('containerId', $element['type']);
                        } else {
                            $elem = $this->form->addUserSelect($name, $label);
                        }
                        break;

                    case self::USER_SELECT_SEARCH:
                        if(!array_key_exists('containerId', $element) && (!array_key_exists($element['type'], $this->skipElementAttributes) || (array_key_exists($element['type'], $this->skipElementAttributes) && !in_array('containerId', $this->skipElementAttributes[$element['type']])))) {
                            $this->throwExceptionForUnsetAttribute('containerId', $element['type']);
                        } else {
                            $elem = $this->form->addUserSelectSearch($name, $label);
                        }
                        break;

                    case self::SELECT_SEARCH:
                        if(!array_key_exists('actionName', $element)) {
                            $this->throwExceptionForUnsetAttribute('actionName', $element['type']);
                        }
                        if(!array_key_exists('searchByLabel', $element)) {
                            $this->throwExceptionForUnsetAttribute('searchByLabel', $element['type']);
                        }

                        $elem = $this->form->addPresenterSelectSearch($element['actionName'], $this->customUrlParams, $name, $element['searchByLabel'], $label);
                        break;

                    case self::PROCESS_SELECT_SEARCH:
                        $elem = $this->form->addPresenterSelectSearch('searchProcesses', $this->customUrlParams, $name, 'Search processes:', $label);
                        break;

                    case self::DOCUMENT_SELECT_SEARCH:
                        $elem = $this->form->addPresenterSelectSearch('searchDocuments', $this->customUrlParams, $name, 'Search documents:', $label);
                        break;
                }
            } else {
                switch($element['type']) {
                    case self::TEXT:
                        $elem = $this->form->addTextInput($name, $label);
                        break;
                    
                    case self::PASSWORD:
                        $elem = $this->form->addPasswordInput($name, $label);
                        break;

                    case self::NUMBER:
                        $elem = $this->form->addNumberInput($name, $label);
                        break;

                    case self::SELECT:
                        $elem = $this->form->addTextInput($name, $label);
                        break;

                    case self::CHECKBOX:
                        $elem = $this->form->addCheckboxInput($name, $label);
                        break;

                    case self::DATE:
                        $elem = $this->form->addDateInput($name, $label);
                        break;

                    case self::DATETIME:
                        $elem = $this->form->addDateTimeInput($name, $label);
                        break;

                    case self::EMAIL:
                        $elem = $this->form->addEmailInput($name, $label);
                        break;

                    case self::TEXTAREA:
                        $elem = $this->form->addTextArea($name, $label);
                        break;

                    case self::TIME:
                        $elem = $this->form->addTimeInput($name, $label);
                        break;

                    case self::SUBMIT:
                        if(!array_key_exists('text', $element)) {
                            $this->throwExceptionForUnsetAttribute('text', $element['type']);
                        } else {
                            $elem = $this->form->addSubmit($element['text'], 'btn_submit');
                        }
                        break;

                    case self::BUTTON:
                        if(!array_key_exists('text', $element)) {
                            $this->throwExceptionForUnsetAttribute('text', $element['type']);
                        } else {
                            $elem = $this->form->addButton($element['text']);
                        }
                        break;

                    case self::ACCEPT_BUTTON:
                        $elem = $this->form->addButton(ProcessInstanceOperations::toString(ProcessInstanceOperations::ACCEPT));
                        $url = Router::generateUrl(array_merge($this->formHandleButtonsParams, ['operation' => 'accept']));
                        if(!$this->isEditor) {
                            $elem->setOnClick('location.href=\'' . $url . '\';');
                        }
                        break;

                    case self::CANCEL_BUTTON:
                        $elem = $this->form->addButton(ProcessInstanceOperations::toString(ProcessInstanceOperations::CANCEL));
                        $url = Router::generateUrl(array_merge($this->formHandleButtonsParams, ['operation' => 'cancel']));
                        if(!$this->isEditor) {
                            $elem->setOnClick('location.href=\'' . $url . '\';');
                        }
                        break;

                    case self::FINISH_BUTTON:
                        $elem = $this->form->addButton(ProcessInstanceOperations::toString(ProcessInstanceOperations::FINISH));
                        $url = Router::generateUrl(array_merge($this->formHandleButtonsParams, ['operation' => 'finish']));
                        if(!$this->isEditor) {
                            $elem->setOnClick('location.href=\'' . $url . '\';');
                        }
                        break;

                    case self::ARCHIVE_BUTTON:
                        $elem = $this->form->addButton(ProcessInstanceOperations::toString(ProcessInstanceOperations::ARCHIVE));
                        $url = Router::generateUrl(array_merge($this->formHandleButtonsParams, ['operation' => 'archive']));
                        if(!$this->isEditor) {
                            $elem->setOnClick('location.href=\'' . $url . '\';');
                        }
                        break;

                    case self::REJECT_BUTTON:
                        $elem = $this->form->addButton(ProcessInstanceOperations::toString(ProcessInstanceOperations::REJECT));
                        $url = Router::generateUrl(array_merge($this->formHandleButtonsParams, ['operation' => 'reject']));
                        if(!$this->isEditor) {
                            $elem->setOnClick('location.href=\'' . $url . '\';');
                        }
                        break;

                    case self::LABEL:
                        if(!array_key_exists('text', $element)) {
                            $this->throwExceptionForUnsetAttribute('text', $element['type']);
                        } else {
                            $elem = $this->form->addLabel($name, $element['text']);
                        }
                        break;

                    case self::USER_SELECT:
                    case self::USER_SELECT_SEARCH:
                    case self::SELECT_SEARCH:
                        $elem = $this->form->addTextInput($name, $label);

                        break;

                    case self::FILE:
                        if($this->container !== null) {
                            $elem = $this->form->addFileLink($name, $label);
                        }

                        break;
                }
            }

            // ELEMENT ATTRIBUTES PROCESSING
            if($elem !== null) {
                if(array_key_exists('attributes', $element)) {
                    foreach($element['attributes'] as $attrName) {
                        switch($attrName) {
                            case 'required':
                                if(method_exists($elem, 'setRequired')) {
                                    $elem->setRequired();
                                } else {
                                    $this->throwExceptionForUnsupportedAttribute($attrName, $element['type']);
                                }

                                break;

                            case 'readonly':
                                if(method_exists($elem, 'setReadonly')) {
                                    $elem->setReadonly();
                                } else {
                                    $this->throwExceptionForUnsupportedAttribute($attrName, $element['type']);
                                }

                                break;
                        }
                    }
                }

                // BUTTON ONCLICK ACTION
                if($elem instanceof Button && array_key_exists('onClick', $element)) {
                    $elem->setOnClick($element['onClick']);
                }
                
                // ELEMENT VALUE
                if(array_key_exists('value', $element)) {
                    if(method_exists($elem, 'setValue')) {
                        $elem->setValue($element['value']);
                    }
                }

                // SELECT ELEMENT VALUES
                if($elem instanceof Select) {
                    if(array_key_exists('values', $element)) {
                        foreach($element['values'] as $value => $text) {
                            $isSelected = false;
    
                            if(array_key_exists('selectedValue', $element)) {
                                if($value == $element['selectedValue']) {
                                    $isSelected = true;
                                }
                            }
    
                            $elem->addRawOption($value, $text, $isSelected);
                        }
                    } else if(array_key_exists('valuesFromConst', $element)) {
                        $const = $element['valuesFromConst'];

                        if(class_exists($const)) {
                            if(is_a($const, AConstant::class, true)) {
                                foreach($const::getAll() as $value => $text) {
                                    $elem->addRawOption($value, $text);
                                }
                            } else {
                                throw new GeneralException('Class \'' . $const . '\' does not extend \'AConstant\' abstract class.');
                            }
                        } else {
                            throw new GeneralException('Class \'' . $const . '\' does not exist.');
                        }
                    } else if(array_key_exists('valuesFromInternalMetadata', $element)) {
                        $metadataName = $element['valuesFromInternalMetadata'];

                        if($this->processId !== null && $this->containerId !== null) {
                            try {
                                $process = $this->form->app->processManager->getProcessEntityById($this->processId);

                                $container = $this->form->app->containerManager->getContainerById($this->containerId);

                                $conn = $this->form->app->dbManager->getConnectionToDatabase($container->getDefaultDatabase()->getName());

                                $tlogRepository = new TransactionLogRepository($conn, $this->form->app->logger);

                                $processMetadataRepository = new ProcessMetadataRepository($conn, $this->form->app->logger, $tlogRepository, $this->form->app->currentUser->getId());

                                $uniqueProcessId = $process->getUniqueProcessId();

                                $qb = $processMetadataRepository->composeQueryForProcessMetadata($uniqueProcessId);

                                $qb->andWhere('title = ?', [$metadataName])
                                    ->execute();

                                $row = $qb->fetch();

                                if($row === null) {
                                    throw new GeneralException('No metadata with title \'' . $metadataName . '\' exists.');
                                }

                                $metadataId = $row['metadataId'];

                                $qb = $processMetadataRepository->composeQueryForProcessMetadataValues($metadataId);

                                $qb->execute();

                                while($row = $qb->fetchAssoc()) {
                                    $value = $row['metadataKey'];
                                    $text = $row['title'];

                                    $elem->addRawOption($value, $text);
                                }
                            } catch(AException $e) {
                                throw new GeneralException('Could not obtain metadata values for metadata \'' . $metadataName . '\'.', $e);
                            }
                        }
                    }
                } else if($elem instanceof FileLink) {
                    $hash = $this->formData[$name]['hash'];

                    $file = $this->container->fileStorageManager->getFileByHash($hash);

                    $fileUrl = Router::generateUrl([
                        'page' => 'User:FileStorage',
                        'action' => 'download',
                        'hash' => $hash
                    ]);

                    $fileSize = UnitConversionHelper::convertBytesToUserFriendly($file->filesize);

                    $elem->setFileUrl($fileUrl);
                    $elem->setFileName($file->filename . ' (' . $fileSize . ')');
                }

                // FORM DATA
                if(!$this->formData !== null) {
                    foreach($this->formData as $fdk => $fdv) {
                        if($element['name'] == $fdk) {
                            if(method_exists($elem, 'setValue')) {
                                $elem->{'setValue'}($fdv);
                            } else if(method_exists($elem, 'setContent')) {
                                $elem->{'setContent'}($fdv);
                            }

                            if(method_exists($elem, 'setReadonly')) {
                                $elem->setReadonly();
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Renders the form to HTML code
     */
    public function render(): string {
        $this->process();

        return $this->form->renderElementsOnly();
    }

    /**
     * Returns the instance of FormBuilder2 with all the elements
     */
    public function getFormBuilder(): FormBuilder2 {
        $this->process();

        return $this->form;
    }

    /**
     * Sets form data to be filled into the form
     * 
     * @param array $data Form data array
     */
    public function setFormData(array $data) {
        $this->formData = $data;
    }

    /**
     * Calls after submit reducer
     */
    public function callAfterSubmitReducer() {
        $this->callAfterSubmitReducer = true;
        $this->form->setCallAfterSubmitReducer(true);
    }

    /**
     * Removes buttons
     */
    public function removeButtons() {
        $this->skipElementTypes = [
            self::ACCEPT_BUTTON,
            self::ARCHIVE_BUTTON,
            self::BUTTON,
            self::CANCEL_BUTTON,
            self::FINISH_BUTTON,
            self::REJECT_BUTTON,
            self::SUBMIT
        ];
    }

    /**
     * Sets form handle buttons URL parameters
     * 
     * @param array $params URL parameters
     */
    public function setFormHandleButtonsParams(array $params) {
        $this->formHandleButtonsParams = $params;
    }

    /**
     * Throws exception when an unsupported attribute is set
     * 
     * @param string $attrName Attribute name
     * @param string $elementType Element type
     * @throws GeneralException
     */
    private function throwExceptionForUnsupportedAttribute(string $attrName, string $elementType) {
        throw new GeneralException('Element \'' . $elementType . '\' does not support attribute \'' . $attrName . '\'.');
    }

    /**
     * Throws exception when an attribute is not set
     * 
     * @param string $attrName Attribute name
     * @param string $elementType Element type
     * @throws GeneralException
     */
    private function throwExceptionForUnsetAttribute(string $attrName, string $elementType) {
        throw new GeneralException('Attribute \'' . $attrName . '\' must be set if type is \'' . $elementType . '\'.');
    }
}

?>