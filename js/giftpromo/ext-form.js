(function (a) {
    function b() {
    }

    for (var c = "assert,count,debug,dir,dirxml,error,exception,group,groupCollapsed,groupEnd,info,log,markTimeline,profile,profileEnd,time,timeEnd,trace,warn".split(","), d; !!(d = c.pop());) {
        a[d] = a[d] || b;
    }
})
(function () {
    try {
        console.log();
        return window.console;
    } catch (a) {
        return (window.console = {});
    }
}());

/**
 * New way of doing select gifts without forms.
 * This allows for nested placement of select list, inside existing forms.
 *
 * An example is gomage checkout, where checkout is one big form.
 *
 */
giftSelect = Class.create();
giftSelect.prototype = {
    initialize: function (element_id, action, product_id, rule_id, selected_gift_item_key, gift_parent_item_id, qty_override) {
        this.element_id = element_id;
        this.action = action;
        this.product_id = product_id;
        this.rule_id = rule_id;
        this.selected_gift_item_key = selected_gift_item_key;
        this.gift_parent_item_id = gift_parent_item_id;
        this.validator = null;
        this.form = null;
        this.qty_override = qty_override;
    },

    submitFromReview: function () {
        this.is_checkout_review = 1;
        this.submit();
    },

    submit: function () {
        // generate a form on the fly, and then submit that
        this.form = new Element('form',
            {
                method: 'post',
                action: this.action,
                id: "giftpromo_select_form_" + this.element_id,
                "data-element": this.element_id
            }).insert(new Element('input',
                {
                    name: 'product',
                    value: this.product_id,
                    type: 'text'
                })).insert(new Element('input',
                {
                    name: 'rule_id',
                    value: this.rule_id,
                    type: 'text'
                })).insert(new Element('input',
                {
                    name: 'selected_gift_item_key',
                    value: this.selected_gift_item_key,
                    type: 'text'
                }));
        if (this.gift_parent_item_id) {
            this.form.insert(new Element('input',
                {
                    name: 'gift_parent_item_id',
                    value: this.gift_parent_item_id,
                    type: 'text'
                }));
        }
        if (this.is_checkout_review) {
            this.form.insert(new Element('input',
                {
                    name: 'is_checkout_review',
                    value: 1,
                    type: 'text'
                }));
        }
        if (this.qty_override) {
            this.form.insert(new Element('input',
                {
                    name: 'qty_override',
                    value: this.qty_override,
                    type: 'text'
                }));
        }
        var formFieldList = this.getElementsByTagNames('input,select,textarea', $$('.' + this.element_id)[0]);
        var that = this
        formFieldList.each(function (element) {
            if (element.name == "giftselect-multiple") {
                //console.log(element);
            } else {
                element.removeClassName('validation-failed');
                var copy = Element.clone(element, true);
                copy.setValue(element.getValue());
                that.form.insert(copy);
            }
        });
        if (this.validateForm()) {
            this.processSubmit();
            return true;
        }
        return false;
    },
    validateForm: function () {
        // inject into DOM, as validate wants to find element and use that
        $$('body')[0].insert(this.form);
        this.validator = new Validation(this.form, {'onFormValidate': this.onFormValidate});
        if (this.validator && this.validator.validate()) {
            return true;
        }
        Element.remove(this.form);
        return false;
    },
    processSubmit: function () {
        Element.remove(this.form);
        $$('.giftselect-btn-cart').each(function (element) {
            element.disabled = true;
            element.addClassName('disabled');
        });
        this.form.request({
            onCreate: function () {
                try {
                    dialog = new ModalView.Box('giftselect-progress');
                    $$('.giftselect-progress')[0].show();
                } catch (e) {
                    console.log(e);
                }
            },
            onComplete: function (response) {
                if (typeof checkout !== "undefined") {
                    //opc
                    if (typeof checkout.reloadReviewBlock == "function") {
                        var updater = new Ajax.Updater('checkout-review-load', checkout.reviewUrl, {
                            method: 'get',
                            evalScripts: true,
                            onFailure: function (response) {
                                window.location.reload();
                            },
                            onComplete: function (response) {
                                $$('.giftselect-progress')[0].hide();
                            }
                        });
                    } else if (typeof checkout.LightcheckoutSubmit == "function") {
                        $$('.giftselect-progress')[0].hide();
                        // gomage Checkout
                        checkout.submit(checkout.getFormData(), 'get_totals');
                    } else {
                        window.location.reload();
                    }
                } else {
                    window.location.reload();
                }
            },
            onFailure: function (response) {
                window.location.reload();
            },
            onException: function (response) {
                alert('There was an exception during the transaction.');
            }
        });
    },
    getElementsByTagNames: function (list, obj) {
        if (!obj) var obj = document;
        var tagNames = list.split(',');
        var resultArray = new Array();
        for (var i = 0; i < tagNames.length; i++) {
            var tags = obj.getElementsByTagName(tagNames[i]);
            for (var j = 0; j < tags.length; j++) {
                resultArray.push(tags[j]);
            }
        }
        var testNode = resultArray[0];
        if (!testNode) return [];
        if (testNode.sourceIndex) {
            resultArray.sort(function (a, b) {
                return a.sourceIndex - b.sourceIndex;
            });
        }
        else if (testNode.compareDocumentPosition) {
            resultArray.sort(function (a, b) {
                return 3 - (a.compareDocumentPosition(b) & 6);
            });
        }
        return resultArray;
    },
    onFormValidate: function (result, form) {
        // reverse the form, placing validation errors and messages
        // back into select display element
        if (result == false) {
            form.getElements().each(function (item) {
                if (item.id && item.hasClassName('validation-failed')) {
                    $$('.' + form.getAttribute('data-element') + ' #' + item.id)[0].addClassName('validation-failed');
                    $$('.' + form.getAttribute('data-element') + ' #' + item.id)[0].focus();
                }
            });
            dialog = new ModalView.Box('giftselect-validation-notice');
            $$('.giftselect-validation-notice')[0].show();
        }
    }
}

multiGiftSelect = Class.create();
multiGiftSelect.prototype = Object.extend(new giftSelect(), {
    initialize: function (element_id, action, rule_id) {
        this.element_id = element_id;
        this.action = action;
        this.rule_id = rule_id;
        this.validator = null;
        this.form = null;
    },

    submit: function () {
        this.form = new Element('form',
            {
                method: 'post',
                action: this.action,
                id: "giftpromo_select_form_" + this.element_id,
                "data-element": this.element_id
            });
        // iterate each gift selectable item
        // if the checkbox is checked, get the formfields for that gift select item
        // and push to form.
        $$('.gift-select-item').each(function (giftselectitem) {
            var tags = giftselectitem.getElementsByTagName('input');
            for (var j = 0; j < tags.length; j++) {
                if (tags[j].type == 'checkbox' && tags[j].checked) {
                    var formFieldList = this.getElementsByTagNames('input,select,textarea', tags[j].up());
                    formFieldList.each(function (element) {
                        element.removeClassName('validation-failed');
                        if (element.name == "giftselect-multiple") {
                            this.form.insert(new Element('input',
                                {
                                    name: 'multi_product[]',
                                    value: element.getAttribute('data-itemid'),
                                    type: 'text'
                                })).insert(new Element('input',
                                {
                                    name: 'multi_rule_id[]',
                                    value: element.getAttribute('data-giftruleid'),
                                    type: 'text'
                                })).insert(new Element('input',
                                {
                                    name: 'multi_selected_gift_item_key[]',
                                    value: element.getAttribute('data-giftkey'),
                                    type: 'text'
                                })).insert(new Element('input',
                                {
                                    name: 'multi_gift_parent_item_id[]',
                                    value: element.getAttribute('data-parentid'),
                                    type: 'text'
                                }));
                        }
                    }.bind(this));
                }
            }
        }.bind(this));
        if (this.validateForm()) {
            this.processSubmit();
            return true;
        }
        Element.remove(this.form);
        return false;
    }
});


function removereviewitem(url, element) {
    if ($('del-gift-please-wait')) {
        $('del-gift-please-wait').show();
    }
    new Ajax.Request(url, {
        method: 'get',
        onComplete: function (response) {
            if (typeof checkout.reloadReviewBlock === "function") {
                var updater = new Ajax.Updater('checkout-review-load', checkout.reviewUrl, {
                    method: 'get',
                    evalScripts: true,
                    onFailure: function (response) {
                        window.location.reload();
                    },
                    onComplete: function (response) {
                        if ($('del-gift-please-wait')) {
                            $('del-gift-please-wait').hide();
                        }
                    }
                });
            }
            // gomage Checkout
            if (typeof checkout.LightcheckoutSubmit === "function") {
                if ($('del-gift-please-wait')) {
                    $('del-gift-please-wait').hide();
                }
                checkout.submit(checkout.getFormData(), 'get_totals');
                checkout.hideLoadinfo();
            }

        },
        onFailure: function (response) {
            window.location.reload();
        }

    });
    return false;
}


// DEPRICATED FROM VERSION 2.10.0
// retained for backwards compatibility for clients with custom .phtml

VarienForm.prototype.submit = VarienForm.prototype.submit.wrap(function (submit) {
    if (this.validator && this.validator.validate()) {
        $$('.giftselect-btn-cart').each(function (element) {
            element.disabled = true;
            element.addClassName('disabled');
        });
        // kept for backwards compatibility to customers who have custom templates
        if ($('giftselect-products-list')) {
            console.log('ID BASED giftselect-products-list was DEPRICATED. You are using an older version of the selectgifts.phtml file. Please update your theme version of the file');
            if ($('giftselect-btn-cart')) {
                $('giftselect-btn-cart').each(function (element) {
                    element.disabled = true;
                    element.addClassName('disabled');
                });
            }
            $('giftselect-products-list').childElements().each(function (childElement) {
                if (childElement.nodeName == 'FORM') {
                    var elements = Form.getElements(childElement);
                    elements.each(function (element) {
                        if (element.nodeName == 'BUTTON') {
                            element.disabled = true;
                            element.addClassName('disabled');
                        }
                    });
                }
            });
        }
        // is this a request via checkout?
        if ($('is_checkout_review') != undefined) {
            if ($('add-gift-please-wait')) {
                $('add-gift-please-wait').show();
            }
            this.form.request({
                onComplete: function (response) {
                    if (typeof checkout.reloadReviewBlock === "function") {
                        checkout.reloadReviewBlock();
                    }
                    // gomage Checkout
                    if (typeof checkout.LightcheckoutSubmit === "function") {
                        checkout.submit(checkout.getFormData(), 'get_totals');
                    }
                    if ($('add-gift-please-wait')) {
                        $('add-gift-please-wait').hide();
                    }
                },
                onFailure: function (response) {
                    window.location.reload();
                }
            });
        } else {
            submit();
        }
    }
});

function removereviewitem(url, element) {
    if ($('del-gift-please-wait')) {
        $('del-gift-please-wait').show();
    }
    new Ajax.Request(url, {
        method: 'get',
        onComplete: function (response) {
            if (typeof checkout.reloadReviewBlock == "function") {
                var updater = new Ajax.Updater('checkout-review-load', checkout.reviewUrl, {
                    method: 'get',
                    evalScripts: true,
                    onFailure: function (response) {
                        window.location.reload();
                    },
                    onComplete: function (response) {
                        if ($('del-gift-please-wait')) {
                            $('del-gift-please-wait').hide();
                        }
                    }
                });
            } else if (typeof checkout.LightcheckoutSubmit == "function") {
                if ($('del-gift-please-wait')) {
                    $('del-gift-please-wait').hide();
                }
                checkout.submit(checkout.getFormData(), 'get_totals');
                checkout.hideLoadinfo();
            } else {
                window.location.reload();
            }

        },
        onFailure: function (response) {
            window.location.reload();
        }

    });
    return false;
}

