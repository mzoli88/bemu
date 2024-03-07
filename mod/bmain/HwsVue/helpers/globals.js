export default {
    install: function (App) {
        App.config.globalProperties.getJog = this.getJog;
        App.config.globalProperties.hasPerm = this.hasPerm;
        App.config.globalProperties.getUserData = this.getUserData;
        App.config.globalProperties.dd = global.dd;
        App.config.globalProperties.isSysAdmin = this.isSysAdmin;
        App.config.globalProperties.getStore = global.getStore;

        App.config.globalProperties.empty = global.empty;
        App.config.globalProperties.isString = global.isString;
        App.config.globalProperties.isNumeric = global.isNumeric;
        App.config.globalProperties.isNumber = global.isNumber;
        App.config.globalProperties.isFunction = global.isFunction;
        App.config.globalProperties.isArray = global.isArray;
        App.config.globalProperties.isObject = global.isObject;

        App.config.globalProperties.hasSlot = this.hasSlot;
    },

    hasSlot: function () {
        var args = arguments;
        if (args.length == 0) args = ['default'];
        for (var i in args) {
            let type = arguments[i];
            type = type || 'default';
            if (!this.$slots[type]) continue;
            if (this.$slots[type]().filter(v => v.key !== '_default').length == 0) continue;
            return true;
        }
        return false
    },

    isSysAdmin: function () {
        return global.isSysAdmin(Array.prototype.slice.call(arguments));
    },

    getJog: function () {
        return global.getJog(Array.prototype.slice.call(arguments));
    },

    hasPerm: function () {
        return global.hasPerm(Array.prototype.slice.call(arguments));
    },

    getUserData: function () {
        return global.getUserData();
    }
}