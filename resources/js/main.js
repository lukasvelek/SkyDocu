/**
 * Sleeps
 * @param {number} ms Milliseconds to sleep
 * @returns {Promise}
 */
async function sleep(ms) {
    return new Promise(r => setTimeout(r, ms));
}

/**
 * Closes a flash message
 * @param {string} _id 
 */
function closeFlashMessage(_id) {
    $("#" + _id).remove();
}

/**
 * Automatically hides a flash message
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