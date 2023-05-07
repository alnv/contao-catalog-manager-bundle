Vue.component( 'custom-option-wizard-field', {
    data: function () {
        return {
            options: [],
            newValue: null,
            newFormVisible: false
        }
    },
    methods: {
        checked: function (strValue) {
            if (Array.isArray(this.value)) {
                return this.value.indexOf(strValue) !== -1;
            }
            return strValue === this.value;
        },
        setCssClass: function() {
            let objCssClass = {};
            if (this.eval['tl_class']) {
                objCssClass[this.eval['tl_class']] = true;
            }
            if (this.eval['mandatory']) {
                objCssClass['mandatory'] = true;
            }
            objCssClass['single'] = !this.eval.multiple;
            objCssClass[this.name] = true;
            return objCssClass;
        },
        addOption: function () {
            if (!this.newFormVisible) {
                this.newFormVisible = true;
                return null;
            }
            if (this.newValue && this.newFormVisible) {
                this.$http.post('/form-manager/addOption', {
                    table: this.eval['_table'],
                    option: this.newValue,
                    name: this.name
                },{
                    emulateJSON: true,
                    'Content-Type': 'application/x-www-form-urlencoded'
                }).then(function (objResponse) {
                    if (objResponse.body && objResponse.ok) {
                        this.$set(this.options, this.options.length, {
                            value: objResponse.body.value,
                            label: objResponse.body.label,
                            delete: true
                        });
                        this.value.push(objResponse.body.value);
                    }
                }.bind(this));
            }
            this.newValue = null;
            this.newFormVisible = false;
        },
        deleteOption: function (option) {
            for (let i = this.options.length-1; i > -1; i--) {
                if (this.options[i] === option) {
                    this.$http.post('/form-manager/deleteOption', {
                        table: this.eval['_table'],
                        option: this.options[i]['value'],
                        name: this.name,
                        index: i
                    },{
                        emulateJSON: true,
                        'Content-Type': 'application/x-www-form-urlencoded'
                    }).then(function (objResponse) {
                        if (objResponse.body && objResponse.ok) {
                            for (let j = 0; j < this.value.length; j++) {
                                if (this.value[j] === objResponse.body.value) {
                                    this.value.splice(j,1);
                                }
                            }
                            this.options.splice(objResponse.body.index, 1);
                        }
                    }.bind(this));
                    break;
                }
            }
        }
    },
    watch: {
        value: function() {
            this.$emit('input', this.value);
        }
    },
    mounted: function() {
        this.options = this.eval.options || [];
    },
    props: {
        eval: {
            default: {},
            type: Object,
            required: true
        },
        name: {
            type: String,
            required: true
        },
        value: {
            default: [],
            type: Array
        },
        idPrefix: {
            default: '',
            type: String ,
            required: false
        },
        noLabel: {
            type: Boolean,
            default: false,
            required: false
        }
    },
    template:
        '<div class="field-component custom-option-wizard" v-bind:class="setCssClass()">' +
            '<div class="field-component-container">' +
                '<input type="hidden" :name="name" :value="this.value">' +
                '<p v-if="!noLabel" class="label" v-html="eval.label"></p>' +
                '<div v-for="(option, index) in options" class="checkbox-container" v-bind:class="{\'checked\': checked(option.value)}">' +
                    '<input type="checkbox" v-model="value" :value="option.value" :id="idPrefix + \'id_\' + name + \'_\' + index">' +
                    '<label :for="idPrefix + \'id_\' + name + \'_\' + index" v-html="option.label"></label>' +
                    '<form class="form-delete">' +
                        '<button class="delete button" v-if="option.delete" @click.prevent="deleteOption(option)"><i class="delete"></i></button>' +
                    '</form>' +
                '</div>' +
                '<div class="option-wizard-controller">' +
                   '<form class="form-add">'+
                        '<input v-if="newFormVisible" type="text" class="text" :placeholder="eval.addButtonLabel1" v-model="newValue" @keyup.enter.prevent="addOption">' +
                        '<button @click.prevent="addOption" class="add button"><span v-html="(newFormVisible ? eval.addButtonLabel2 : eval.addButtonLabel1)"></span></button>' +
                    '</form>' +
                '</div>' +
                '<template v-if="!eval.validate"><p class="error" v-for="message in eval.messages">{{ message }}</p></template>' +
                '<div v-if="eval.description" v-html="eval.description" class="info"></div>' +
            '</div>' +
        '</div>'
});
