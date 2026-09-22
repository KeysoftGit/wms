
Vue.component('select2', {
    props: ['options', 'value', 'url', 'placeholder', 'extra', 'selected_item', 'prevalue'],
    template: '#select2-template',
    data: function () {
        var thisVal = this;
        return {
            ajaxOptions: {
                url: this.url,
                dataType: 'json',
                delay: 250,
                tags: true,
                data: function (params) {
                    if (params === undefined || params === null) return this.extra;
                    var q = {
                        search: $.trim(params.term)
                    };
                    return Object.assign({}, thisVal.extra || {}, q);
                },
                processResults: function (data) {
                    var results = data;
                    if (data && data.status === 'success' && Array.isArray(data.data)) {
                        results = data.data;
                    }
                    return {
                        results: results
                    };
                },
                cache: true
            },
        };
    },
    methods: {
        // Emit events back to parent
        syncParent: function(val, text, forceInput) {
            var vm = this;

            // Only sync text if it's meaningful to avoid wiping restored labels
            if (text && text !== "" && text !== "-" && text !== vm.placeholder) {
                vm.$emit('selected_unit', val);
                vm.$emit('selected_text', text);
            }

            // Only sync value if it's a real change or forced (user interaction)
            // This prevents overwriting parent data with 'empty' values during init
            if (forceInput || (val !== undefined && val !== null && String(val) !== String(vm.value))) {
                vm.$emit('input', val);
            }
        },
        initSelect2: function() {
            var vm = this;
            var $el = $(vm.$el);

            var config = {
                placeholder: { id: "-1", text: vm.placeholder || "-" },
                width: '100%',
                theme: 'bootstrap-5'
            };

            if (this.url != undefined) {
                config.ajax = vm.ajaxOptions;
                $el.select2(config)
                .val(this.value)
                .trigger('change.select2')
                .on('change', function (e) {
                    var isQuiet = e && e.params && e.params.quiet;
                    var val = $(this).val();
                    var text = $(this).find("option:selected").text();
                    vm.syncParent(val, text, !isQuiet);
                });
            } else if (vm.options !== undefined) {
                var opt = vm.options;
                if (typeof (vm.options) === "object" && !Array.isArray(vm.options)) {
                    opt = Object.entries(vm.options).map(function (v) {
                        return { id: v[0], text: v[1] };
                    });
                }
                config.data = opt;
                $el.select2(config)
                    .val(this.value)
                    .trigger('change.select2')
                    .on('change', function (e) {
                        var isQuiet = e && e.params && e.params.quiet;
                        var val = $(this).val();
                        var text = $(this).find("option:selected").text();
                        vm.syncParent(val, text, !isQuiet);
                    });
            }

            this.handlePrevalue(this.prevalue || this.value);
        },
        handlePrevalue: function(value) {
            var vm = this;
            if (value === null || value === undefined || value === "") {
                if ($(vm.$el).val() !== "") {
                    $(vm.$el).val("").trigger({ type: 'change', params: { quiet: true } });
                }
                return;
            }

            var $el = $(vm.$el);
            var values = Array.isArray(value) ? value : [value];
            var missingValues = [];

            // Check what values are missing from current options
            for (var i = 0; i < values.length; i++) {
                var found = false;
                $el.find("option").each(function() {
                    if (String($(this).val()).toLowerCase() === String(values[i]).toLowerCase()) {
                        found = true;
                        return false;
                    }
                });
                if (!found) missingValues.push(values[i]);
            }

            if (missingValues.length === 0) {
                // All values exist, just need to ensure they are selected
                var currentVal = $el.val();
                var normalizedValue = Array.isArray(value) ? value.map(String).join(",") : String(value);
                var normalizedCurrent = Array.isArray(currentVal) ? currentVal.map(String).join(",") : String(currentVal);

                if (normalizedValue.toLowerCase() !== normalizedCurrent.toLowerCase()) {
                    // Find actual correctly-cased IDs to use with .val()
                    var correctIds = [];
                    for (var j = 0; j < values.length; j++) {
                        $el.find("option").each(function() {
                            if (String($(this).val()).toLowerCase() === String(values[j]).toLowerCase()) {
                                correctIds.push($(this).val());
                                return false;
                            }
                        });
                    }
                    $el.val(Array.isArray(value) ? correctIds : correctIds[0]).trigger({ type: 'change', params: { quiet: true } });
                } else {
                    // Values match, but sync labels just in case
                    var text = $el.find("option:selected").text();
                    vm.syncParent(currentVal, text, false);
                }
                return;
            }

            if (vm.url) {
                var params = Object.assign({}, vm.extra || {});
                params.search = missingValues[0];

                axios.get(vm.url, { params: params }).then(function (res) {
                    var data = res.data;
                    if (data && data.status === 'success' && Array.isArray(data.data)) {
                        data = data.data;
                    }

                    if (data && data.length > 0) {
                        var foundSomething = false;
                        var targetValues = values.map(function(v) { return String(v).toLowerCase(); });
                        var correctIds = [];

                        for (var idx = 0; idx < data.length; idx++) {
                            var itemId = String(data[idx].id);
                            if (targetValues.indexOf(itemId.toLowerCase()) !== -1) {
                                correctIds.push(data[idx].id);
                                if ($el.find("option[value='" + data[idx].id + "']").length === 0) {
                                    var option = new Option(data[idx].text, data[idx].id, true, true);
                                    $el.append(option);
                                    foundSomething = true;
                                }
                            }
                        }

                        // Use the correctly-cased IDs from the server
                        if (correctIds.length > 0) {
                            $el.val(Array.isArray(value) ? correctIds : correctIds[0]).trigger({ type: 'change', params: { quiet: true } });
                        }
                    }
                }).catch(function (err) {
                    console.error('Select2 Restoration Error:', err);
                });
            }
        }
    },
    mounted: function () {
        this.initSelect2();
    },
    watch: {
        value: function(val) {
            this.handlePrevalue(val);
        },
        prevalue: function (value) {
            this.handlePrevalue(value);
        },
        options: function (value) {
            var $el = $(this.$el);
            $el.select2('destroy').empty();
            this.initSelect2();
        },
        url: function (value) {
            this.ajaxOptions.url = value;
            var config = {
                placeholder: { id: "-1", text: this.placeholder || "-" },
                width: '100%',
                ajax: this.ajaxOptions,
                theme: 'bootstrap-5'
            };
            $(this.$el).select2(config);

            // Re-fetch label if there is a current value
            if (this.value) {
                this.handlePrevalue(this.value);
            }
        }
    },
    destroyed: function () {
        $(this.$el).off().select2('destroy');
    }
});
