
Vue.component('select2', {
    props: ['options', 'value', 'url', 'placeholder', 'extra', 'selected_item', 'modal'],
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
                    if (thisVal.extra !== undefined) {
                        thisVal.extra.keyword = $.trim(params.term);
                        return thisVal.extra;
                    }
                    else {
                        return {
                            search: $.trim(params.term)
                        };
                    }
                },
                processResults: function (data) {
                    return {
                        results: data
                    };
                },
                cache: true
            }
        };
    },
    mounted: function () {
        var vm = this;

        if (this.url != undefined) {
            $(vm.$el)
                // init select2
                .select2({
                    //dropdownParent: $('#modal-start-treatment'),
                    dropdownParent: $('#' + this.modal),
                    placeholder: {
                        id: "-1",
                        text: vm.placeholder
                    },
                    width: '100%',
                    ajax: vm.ajaxOptions
                })
                .val(this.value)
                .trigger('change')
                // emit event on change.
                .on('change', function () {
                    // vm.$emit('input', this.value)
                    vm.$emit('input', $(this).val());
                    vm.$emit('selected_unit', $(this).val());
                    // debugger;
                });


            if (this.value !== 0 && this.value !== null) {
                //there is preselected value, we need to query right away.
                var initData = axios.get(this.url, {
                    params: this.extra
                }).then(function (val) {
                    if (val.data.length > 0) {
                        for (var idx = 0; idx < val.data.length; idx++) {

                            if (val.data[idx].id == vm.value) {
                                var option = new Option(val.data[idx].text, val.data[idx].id, true, true);
                                $(vm.$el).append(option).trigger('change');

                                // manually trigger the `select2:select` event
                                // $(vm.$el).trigger({
                                //     type: 'select2:select',
                                //     params: {
                                //         data: val.data[idx]
                                //     }
                                // });
                            }
                        }
                    }
                }).catch(function (err) {
                    console.log(err);
                });
            }
        } else if (vm.options !== undefined) {
            var opt = vm.options;
            if (typeof (vm.options) === "object") {
                opt = Object.entries(vm.options).map(function (v) {
                    return { id: v[0], text: v[1] };
                });
            }
            $(vm.$el)
                // init select2
                .select2({ data: opt })
                .val(this.value)
                .trigger('change')
                // emit event on change.
                .on('change', function () {
                    // vm.$emit('input', this.value)
                    vm.$emit('input', $(this).val());
                    vm.$emit('selected_unit', $(this).val());
                });
        }

    },
    watch: {
        value: function (value) {
            if(!value){
                $(this.$el).empty().trigger('change');
            }
            else if ([...value].sort().join(",") !== [...$(this.$el).val()].sort().join(","))
                $(this.$el).val(value).trigger('change');
            // update value
            // $(this.$el)
            //     .val(value)
            //     .trigger('change');
        },
        options: function (value) {
            this.options = value;
            // update value
            $(this.$el)
                // init select2
                .select2({ data: this.options })
                .val(this.value)
                .trigger('change')
        },
        url: function (value) {
            this.ajaxOptions.url = this.url;
            $(this.$el).select2({
                placeholder: {
                    id: "-1",
                    text: value
                },
                width: '100%',
                ajax: this.ajaxOptions
            });
        },
        placeholder: function (value) {
            this.placeholder = value;
            $(this.$el).select2({
                placeholder: {
                    id: "-1",
                    text: value
                },
                width: '100%',
                ajax: this.ajaxOptions
            })
        },
        extra: function (value) {
            this.extra = value;
            $(this.$el).select2({
                placeholder: {
                    id: "-1",
                    text: value
                },
                width: '100%',
                ajax: this.ajaxOptions
            });
        }

    },
    destroyed: function () {
        $(this.$el).off().select2('destroy')
    }
});
