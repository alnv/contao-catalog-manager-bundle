'use strict';

const WatchlistFormComponent = Vue.component('watchlist-form', {
    data: function () {
        return {
            items: 1,
            hasItems: false
        };
    },
    methods: {
        getButtonLabel: function () {
            if (this.hasItems) {
                return this.buttonRemoveLabel;
            }
            return this.buttonAddLabel;
        },
        updateWishlist: function () {
            let parameter = {
                data: this.data,
                items: this.items
            };
            this.$http.post('/catalog-manager/watchlist/update', parameter, {
                emulateJSON: true,
            }).then(function (res) {
                this.hasItems = !!res.body.id;
                this.items = res.body.units;
            }.bind(this));
        },
        deleteOrAdd: function () {
            if (this.hasItems) {
                this.items = 0;
            }
            this.updateWishlist();
        },
        onChange: function () {
            //
        }
    },
    mounted: function () {
        this.items = this.units;
        this.hasItems = this.added;
    },
    props: {
        data: {
            type: String,
            required: true
        },
        units: {
            type: Number,
            required: false,
            default: 1
        },
        added: {
            type: Boolean,
            required: false,
            default: false
        },
        buttonAddLabel: {
            type: String,
            required: false,
            default: '',
        },
        buttonRemoveLabel: {
            type: String,
            required: false,
            default: '',
        },
        buttonUnitsLabel: {
            type: String,
            required: false,
            default: '',
        },
        useUnits: {
            type: Boolean,
            required: false,
            default: true
        }
    },
    template:
        '<div class="ce_watchlist-form" v-bind:class="{added:hasItems}">' +
            '<div class="watchlist-form">' +
                '<div class="watchlist-units" v-if="useUnits">' +
                    '<input type="number"  v-model="items" min="0">' +
                    '<button v-if="hasItems" @click="updateWishlist" class="watchlist-update-button"><span v-html="buttonUnitsLabel"></span></button>' +
                '</div>' +
                '<button @click="deleteOrAdd" class="watchlist-button" v-bind:class="{\'remove-button\':hasItems, \'add-button\':!hasItems}"><span v-html="getButtonLabel()"></span></button>' +
            '</div>' +
        '</div>'
});