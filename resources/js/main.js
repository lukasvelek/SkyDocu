/**
 * Sleeps
 * 
 * @param {number} ms Milliseconds to sleep
 * @returns {Promise}
 */
async function sleep(ms) {
    return new Promise(r => setTimeout(r, ms));
}

/**
 * Closes a flash message
 * 
 * @param {string} _id 
 */
function closeFlashMessage(_id) {
    $("#" + _id).remove();
}

/**
 * Automatically hides a flash message
 * 
 * @param {string} _divId
 * @param {number} _length 
 */
async function autoHideFlashMessage(_divId, _length) {
    const sleepLength = (_length * 100); // 5s

    for(var s = 1; s <= sleepLength; s++) {
        $("#" + _divId + "-progress-bar").css("width", "" + (100 / sleepLength * s) + "%")
        await sleep((sleepLength / sleepLength));
    }

    closeFlashMessage(_divId);
}

/**
 * Copies text from a text field to the clipboard
 * 
 * @param {string} _elementId
 */
function copyToClipboard(_elementId) {
    var copyText = $("#" + _elementId);
    navigator.clipboard.writeText(copyText.html());
}

/**
 * Copies text from a text field to the clipboard and updates the copy link
 * 
 * @param {string} _elementId 
 * @param {string} _link 
 * @param {string} _beforeText 
 * @param {string} _afterText 
 * @param {number} _timeout 
 */
async function copyToClipboardWithLink(_elementId, _link, _beforeText, _afterText, _timeout) {
    copyToClipboard(_elementId);

    $("#" + _link).html(_afterText);

    await sleep(_timeout);

    $("#" + _link).html(_beforeText);
}

/**
 * Copies predefined text from a text field to the clipboard and updates the copy link
 * 
 * @param {string} _text 
 * @param {string} _link 
 * @param {string} _beforeText 
 * @param {string} _afterText 
 * @param {number} _timeout 
 */
async function copyTextToClipboardWithLink(_text, _link, _beforeText, _afterText, _timeout) {
    navigator.clipboard.writeText(_text);

    $("#" + _link).html(_afterText);

    await sleep(_timeout);

    $("#" + _link).html(_beforeText);
}

/**
 * Displays loading animation in given DOM element
 * 
 * @param {string} _element 
 */
function showLoadingAnimation(_element) {
    $("#" + _element).html("<div id=\"center\"><img src=\"resources/loading.gif\" width=\"64px\"></div>");
}