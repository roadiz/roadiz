import $ from 'jquery'

/**
 * Node Statuses
 */
export default class NodeStatuses {
    constructor () {
        this.$containers = $('.node-statuses, .node-actions')
        this.$icon = $('.node-status header i')
        this.$inputs = this.$containers.find('input[type="checkbox"], input[type="radio"]')
        this.$item = this.$containers.find('.node-statuses-item')
        this.locked = false

        this.itemClick = this.itemClick.bind(this)
        this.containerEnter = this.containerEnter.bind(this)
        this.containerLeave = this.containerLeave.bind(this)
        this.onChange = this.onChange.bind(this)

        this.init()
    }

    init () {
        this.$item.off('click', this.itemClick)
        this.$item.on('click', this.itemClick)

        this.$containers.on('mouseenter', this.containerEnter)
        this.$containers.on('mouseleave', this.containerLeave)

        this.$inputs.off('change', this.onChange)
        this.$inputs.on('change', this.onChange)

        this.$containers.find('.rz-boolean-checkbox').bootstrapSwitch({
            size: 'small',
            onSwitchChange: this.onChange
        })
    }

    containerEnter (event) {
        event.stopPropagation()

        let $container = $(event.currentTarget)
        let $list = $container.find('ul, nav').eq(0)
        let containerHeight = $container.height()
        let listHeight = $list.height()
        let containerOffsetTop = $container.offset().top
        let windowHeight = window.innerHeight
        let fullHeight = containerOffsetTop + listHeight + containerHeight

        if (windowHeight < fullHeight) {
            $container.addClass('reverse')
        }
    }

    containerLeave (event) {
        event.stopPropagation()

        let $container = $(event.currentTarget)
        $container.removeClass('reverse')
    }

    itemClick (event) {
        event.stopPropagation()

        let $input = $(event.currentTarget).find('input[type="radio"]')

        if ($input.length) {
            $input.prop('checked', true)
            $input.trigger('change')
        }
    }

    onChange (event) {
        event.stopPropagation()
        if (this.locked === false) {
            this.locked = true
            let $input = $(event.currentTarget)

            if ($input.length) {
                let statusName = $input.attr('name')
                let statusValue = null

                if ($input.is('input[type="checkbox"]')) {
                    statusValue = Number($input.is(':checked'))
                } else if ($input.is('input[type="radio"]')) {
                    this.$icon[0].className = $input.parent().find('i')[0].className
                    statusValue = $input.val()
                }

                let postData = {
                    '_token': window.Rozier.ajaxToken,
                    '_action': 'nodeChangeStatus',
                    'nodeId': parseInt($input.attr('data-node-id')),
                    'statusName': statusName,
                    'statusValue': statusValue
                }

                $.ajax({
                    url: window.Rozier.routes.nodesStatusesAjax,
                    type: 'post',
                    dataType: 'json',
                    cache: false,
                    data: postData
                })
                .done(() => {
                    window.Rozier.refreshMainNodeTree()
                    window.Rozier.getMessages()
                })
                .fail(data => {
                    data = JSON.parse(data.responseText)
                    window.UIkit.notify({
                        message: data.error_message,
                        status: 'danger',
                        timeout: 3000,
                        pos: 'top-center'
                    })
                })
                .always(() => {
                    this.locked = false
                })
            }
        }
    }
}
