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
        this.always(0)
        this.$nextStepButton = $('#next-step-button')
        this.routes = null
        this.score = 0
    }

    always (index) {
        if (this.routes.length > index) {
            if (typeof this.routes[index].update !== 'undefined') {
                $.ajax({
                    url: this.routes[index].update,
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
        let $row = $('#' + this.routes[index].id)
        let $icon = $row.find('i')
        $icon.removeClass('uk-icon-circle-o')
        $icon.addClass('uk-icon-spin')
        $icon.addClass('uk-icon-spinner')

        let postData = {
            'filename': this.routes[index].filename
        }

        $.ajax({
            url: this.routes[index].url,
            type: 'POST',
            dataType: 'json',
            data: postData,
            success: () => {
                $icon.removeClass('uk-icon-spinner')
                $icon.addClass('uk-icon-check')
                $row.addClass('uk-badge-success')

                // Call post-update route
                if (this.routes[index].postUpdate) {
                    if (this.routes[index].postUpdate instanceof Array &&
                         this.routes[index].postUpdate.length > 1) {
                        // Call clear cache before updating schema
                        $.ajax({
                            url: this.routes[index].postUpdate[0],
                            type: 'POST',
                            dataType: 'json',
                            complete: () => {
                                // Update schema
                                console.log('Calling: ' + this.routes[index].postUpdate[0])
                                $.ajax({
                                    url: this.routes[index].postUpdate[1],
                                    type: 'POST',
                                    dataType: 'json',
                                    complete: () => {
                                        this.always(index + 1)
                                    }
                                })
                            }
                        })
                    } else {
                        $.ajax({
                            url: this.routes[index].postUpdate,
                            type: 'POST',
                            dataType: 'json',
                            complete: () => {
                                this.always(index + 1)
                            }
                        })
                    }
                } else {
                    this.always(index + 1)
                }
            },
            error: (data) => {
                $icon.removeClass('uk-icon-spinner')
                $icon.addClass('uk-icon-warning')
                $row.addClass('uk-badge-danger')

                if (data.responseJSON && data.responseJSON.error) {
                    $row.parent().parent().after('<tr><td class="uk-alert uk-alert-danger" colspan="3">' + data.responseJSON.error + '</td></tr>')
                }
            },
            complete: () => {
                $icon.removeClass('uk-icon-spin')
            }
        })
    }
}
