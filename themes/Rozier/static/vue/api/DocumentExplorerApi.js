import request from 'axios'

/**
 * Fetch Documents from search terms.
 *
 * @param  {String} searchTerms
 * @return Promise
 */
export function getDocumentsFromSearchTerms (searchTerms) {
    const postData = {
        _token: Rozier.ajaxToken,
        _action: 'searchNodesSources',
        search: searchTerms
    }

    return request({
        method: 'GET',
        url: Rozier.routes.documentsAjaxExplorer,
        params: postData
    })
        .then((response) => {
            if (typeof response.data != 'undefined' && response.data.documents) {
                return response.data
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
