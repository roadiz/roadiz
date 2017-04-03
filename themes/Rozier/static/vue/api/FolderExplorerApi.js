import request from 'axios'

/**
 * Fetch documents Folders.
 *
 * @return Promise
 */
export function getFolders () {
    const postData = {
        _token: RozierRoot.ajaxToken,
        _action: 'foldersExplorer',
    }

    return request({
        method: 'GET',
        url: RozierRoot.routes.foldersAjaxExplorer,
        params: postData
    })
        .then((response) => {
            if (typeof response.data != 'undefined' && response.data.folders) {
                return {
                    items: response.data.folders
                }
            } else {
                return null
            }
        })
        .catch((error) => {
            // TODO
            // Log request error or display a message
            throw new Error(error)
        })
}
