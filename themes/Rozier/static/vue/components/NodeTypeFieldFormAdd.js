var nodeTypeFieldFormAdd = new Vue({
    delimiters: ['${', '}'],
    el: '#add-node-type-field-form',
    data: function() {
        return {
            selected: 0
        };
    },
    methods: {
        getNodeTypeFields: function() {
            console.log(Rozier.routes.nodeTypesFieldAjaxList);
            fetch(Rozier.routes.nodeTypesFieldAjaxList)
                .then(function(result) {
                    return result.json();
                })
                .then(function(res) {
                    console.log(res);
                });
        }
    },
    watch: {
        selected: function(newValue) {
            if (newValue == 16) {
                this.getNodeTypeFields();
            }
        }
    }
});
