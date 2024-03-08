global.empty = function (a) {
    if (a === null || a === undefined) return true;
    if (isFunction(a)) return false;
    if (isObject(a)) {
        for (var x in a) { return false; }
        return true;
    }
    return a.length === 0;
}

global.isString = function (a) { return a !== null && a !== undefined && a.constructor === String }

global.isNumeric = function (a) { return isNumber(a) || (isString(a) && !isNaN(parseFloat(a)) && isFinite(a)); }

global.isNumber = function (a) { return a !== null && a !== undefined && a.constructor === Number }

global.isFunction = function (a) { return a !== null && a !== undefined && a.constructor === Function }

global.isArray = function (a) { return a !== null && a !== undefined && a.constructor === Array }

global.isObject = function (a) { return a !== null && a !== undefined && a.constructor === Object }

global.isDate = function (a) { return a !== null && a !== undefined && a.constructor === Date }

global.isRegexp = function (a) { return a !== null && a !== undefined && a.constructor === RegExp }

global.isBoolean = function (a) { return a !== null && a !== undefined && a.constructor === Boolean }
