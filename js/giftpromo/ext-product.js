Object.extend(Product.Config.prototype, {
    initialize: function (config, uniqueid) {
        this.config = config;
        this.taxConfig = this.config.taxConfig;
        this.settings = $$('.super-attribute-select-' + uniqueid);
        this.state = new Hash();
        this.priceTemplate = new Template(this.config.template);
        this.prices = config.prices;

        this.settings.each(function (element) {
            Event.observe(element, 'change', this.configure.bind(this))
        }.bind(this));

        // fill state
        this.settings.each(function (element) {
            var attributeId = element.id.replace(/[a-z]*/, '');
            if (attributeId && this.config.attributes[attributeId]) {
                element.config = this.config.attributes[attributeId];
                element.attributeId = attributeId;
                this.state[attributeId] = false;
            }
        }.bind(this))

        // Init settings dropdown
        var childSettings = [];
        for (var i = this.settings.length - 1; i >= 0; i--) {
            var prevSetting = this.settings[i - 1] ? this.settings[i - 1] : false;
            var nextSetting = this.settings[i + 1] ? this.settings[i + 1] : false;
            if (i == 0) {
                this.fillSelect(this.settings[i])
            }
            else {
                this.settings[i].disabled = true;
            }
            $(this.settings[i]).childSettings = childSettings.clone();
            $(this.settings[i]).prevSetting = prevSetting;
            $(this.settings[i]).nextSetting = nextSetting;
            childSettings.push(this.settings[i]);
        }

        // Set default values - from config and overwrite them by url values
        if (config.defaultValues) {
            this.values = config.defaultValues;
        }

        var separatorIndex = window.location.href.indexOf('#');
        if (separatorIndex != -1) {
            var paramsStr = window.location.href.substr(separatorIndex + 1);
            var urlValues = paramsStr.toQueryParams();
            if (!this.values) {
                this.values = {};
            }
            for (var i in urlValues) {
                this.values[i] = urlValues[i];
            }
        }

        this.configureForValues();
        document.observe("dom:loaded", this.configureForValues.bind(this));
    },
    reloadPrice: function () {
        var price = 0;
        var oldPrice = 0;
        for (var i = this.settings.length - 1; i >= 0; i--) {
            var selected = this.settings[i].options[this.settings[i].selectedIndex];
            if (selected.config) {
                price += parseFloat(selected.config.price);
                oldPrice += parseFloat(selected.config.oldPrice);
            }
        }

        //Dynamic price not supported as yet in gifted configurables
        //optionsPrice.changePrice('config', {'price': price, 'oldPrice': oldPrice});
        //optionsPrice.reload();

        return price;

        if ($('product-price-' + this.config.productId)) {
            $('product-price-' + this.config.productId).innerHTML = price;
        }
        this.reloadOldPrice();
    },
});
