import $ from 'jquery'

/**
 * Import
 */
export default class Import {
    /**
     * Constructor
     * @param {Array} routesArray
     */
    constructor (routesArray) {
        this.routes = routesArray
        this.always(0, this.routes)
        this.$nextStepButton = $('#next-step-button')
        this.score = 0
    }

    always (index, routes) {
        if (routes.length > index) {
            if (typeof routes[index].update !== 'undefined') {
                $.ajax({
                    url: routes[index].update,
                    type: 'POST',
                    dataType: 'json',
                    complete: () => {
                        this.callSingleImport(index)
                    }
                })
            } else {
                this.callSingleImport(index)
            }
        } else if (this.$nextStepButton.length) {
            this.$nextStepButton.removeClass('uk-button-disabled')
        }
    }

    callSingleImport (index) {
        const currentIndex = index
        const routes = this.routes
        let $row = $('#' + routes[currentIndex].id)
        let $icon = $row.find('i')
        $icon.removeClass('uk-icon-circle-o')
        $icon.addClass('uk-icon-spin')
        $icon.addClass('uk-icon-spinner')

        let postData = {
            'filename': routes[currentIndex].filename
        }

        $.ajax({
            url: routes[currentIndex].url,
            type: 'POST',
            dataType: 'json',
            data: postData,
            success: () => {
                $icon.removeClass('uk-icon-spin')
                $icon.removeClass('uk-icon-spinner')
                $icon.addClass('uk-icon-check')
                $row.addClass('uk-badge-success')

                // Call post-update route
                if (routes[currentIndex].postUpdate) {
                    if (routes[currentIndex].postUpdate instanceof Array &&
                         routes[currentIndex].postUpdate.length > 1) {
                        // Call clear cache before updating schema
                        $.ajax({
                            url: routes[currentIndex].postUpdate[0],
                            type: 'POST',
                            dataType: 'json',
                            complete: () => {
                                $.ajax({
                                    url: routes[currentIndex].postUpdate[1],
                                    type: 'POST',
                                    dataType: 'json',
                                    complete: () => {
                                        this.always(currentIndex + 1, routes)
                                    }
                                })
                            }
                        })
                    } else {
                        $.ajax({
                            url: routes[currentIndex].postUpdate,
                            type: 'POST',
                            dataType: 'json',
                            complete: () => {
                                this.always(currentIndex + 1, routes)
                            }
                        })
                    }
                } else {
                    this.always(currentIndex + 1, routes)
                }
            },
            error: (data) => {
                $icon.removeClass('uk-icon-spin')
                $icon.removeClass('uk-icon-spinner')
                $icon.addClass('uk-icon-warning')
                $row.addClass('uk-badge-danger')

                if (data.responseJSON && data.responseJSON.error) {
                    $row.parent().parent().after('<tr><td class="uk-alert uk-alert-danger" colspan="3">' + data.responseJSON.error + '</td></tr>')
                }
            },
            complete: () => {
                $icon.removeClass('uk-icon-spin')
                $icon.removeClass('uk-icon-spinner')
            }
        })
    }
}
