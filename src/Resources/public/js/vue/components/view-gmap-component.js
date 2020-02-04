const ViewGmapComponent = Vue.component( 'view-gmap', {
    data: function () {
        return {
            map: null,
            bounds: null,
            parameters: {},
            locations: [],
            markers: [],
        }
    },
    methods: {
        fetch: function () {
            this.removeMarkers();
            this.$http.get( '/catalog-manager/view-map/' + this.module + '/' + this.page, {
                params: this.parameters
            }).then(function ( objResponse ) {
                if ( objResponse.body && objResponse.ok ) {
                    this.locations = JSON.parse( objResponse.body.locations );
                    this.initMap();
                    this.$parent.clearAlert();
                }
                if ( !objResponse.ok ) {
                    this.$parent.setErrorAlert( '', this );
                }
            });
        },
        onChange: function (shared) {
            this.parameters = shared;
            if ( this.view ) {
                this.$parent.setLoadingAlert( '', this );
            }
            this.fetch();
        },
        setMarkers: function() {
            this.bounds = new google.maps.LatLngBounds();
            for (var i=0;i<this.locations.length;i++) {
                var strTemplate = '';
                var objPosition = new google.maps.LatLng(this.locations[i]['map']['latitude'],this.locations[i]['map']['longitude']);
                var objMarker = new google.maps.Marker({
                    title: this.locations[i]['map']['title'],
                    position: objPosition,
                    map: this.map
                });
                google.maps.event.addListener(objMarker, 'click', this.getInfoContent(strTemplate));
                this.bounds.extend(objPosition);
                this.markers.push(objMarker);
            }
        },
        removeMarkers: function() {
            for (var i=0;i<this.markers.length;i++) {
                this.markers[i].setMap(null);
            }
        },
        initMap: function () {
            if (!this.map) {
                this.map = new google.maps.Map(this.$el.querySelector('.gmap'), {
                    maxZoom: 18
                });
            }
            this.setMarkers();
            this.map.fitBounds(this.bounds);
        },
        getInfoContent: function (content) {
            var objInfoBox = new google.maps.InfoWindow({content: content});
            return function() {
                objInfoBox.setContent(content);
                objInfoBox.open(this.map, this);
            };
        }
    },
    mounted: function () {
        if ( typeof google === 'undefined' ) {
            var objScript = document.createElement('script');
            objScript.src = 'https://maps.googleapis.com/maps/api/js?key=AIzaSyByWU1hQHfJEQjOkUjIBNU1JmyQ9_SG1nY&callback=';
            objScript.defer = true;
            objScript.async = true;
            objScript.onload = function() {
                if (!this.awaitOnChange) {
                    this.fetch();
                }
            }.bind(this);
            document.body.appendChild(objScript);
            return null;
        }
        if (!this.awaitOnChange) {
            this.fetch();
        }
    },
    props: {
        awaitOnChange: {
            type: Boolean,
            default: false,
            required: true
        },
        module: {
            type: String,
            default: null,
            required: true
        },
        page: {
            type: String,
            default: null,
            required: true
        }
    },
    template:
        '<div class="view-gmap-component">' +
            '<transition name="fade">' +
                '<div class="view-gmap-component-container" v-show="locations.length">' +
                    '<div class="gmap" style="height:600px;width:100%"></div>' +
                '</div>' +
            '</transition>' +
            '<loading v-if="!locations.length"></loading>' +
        '</div>'
});