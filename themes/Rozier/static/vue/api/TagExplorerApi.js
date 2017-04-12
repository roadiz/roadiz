import request from 'axios'

/**
 * Fetch documents Folders.
 *
 * @return Promise
 */
export function getTags () {
    const postData = {
        _token: RozierRoot.ajaxToken,
        _action: 'tagsExplorer',
    }

    return request({
        method: 'GET',
        url: RozierRoot.routes.tagsAjaxExplorer,
        params: postData
    })
        .then((response) => {
            if (typeof response.data !== 'undefined' && response.data.tags) {
                return {
                    items: response.data.tags
                }
            } else {
                return {}
            }
        })
        .catch((error) => {
            // TODO
            // Log request error or display a message
            throw new Error(error)
        })
}
