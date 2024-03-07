global.Validators = {

    date: function (value) {
        if (empty(value)) return false;
        if (!/^([1-9][0-9][0-9][0-9]\-[0-9][0-9]\-[0-9][0-9]){1}$/.test(value)) {
            return "Nem megfelelő dátum - a helyes formátum: éééé.hh.nn.!";
        }
        var tmp = value.split('-'),
            year = parseInt(tmp[0]),
            month = parseInt(tmp[1]) - 1,
            day = parseInt(tmp[2]),
            dateObj = new Date(year, month, day);
        if (!(dateObj.getFullYear() == year && dateObj.getMonth() == month && dateObj.getDate() == day)) {
            return "A megadott dátum nem létezik!";
        }
    },

    datetime: function (value) {
        if (empty(value)) return false;
        // dd(value);
        if (!/^[0-9]{4}\-[0-9]{2}\-[0-9]{2}\s[0-9]{2}\:[0-9]{2}$/.test(value)) {
            return "Nem megfelelő dátum idő - a helyes formátum: éééé.hh.nn. óó:pp!";
        }
        var tmp = value.split(' '),
            tmpdate = tmp.shift().split('-'),
            year = parseInt(tmpdate[0]),
            month = parseInt(tmpdate[1]) - 1,
            day = parseInt(tmpdate[2]),
            tmpTime = tmp.shift().split(':'),
            hours = parseInt(tmpTime.shift()),
            minutes = parseInt(tmpTime.shift()),
            dateObj = new Date(year, month, day, hours, minutes);

        // dd(dateObj.getHours(), dateObj.getMinutes(), dateObj.getHours() == hours, dateObj.getMinutes() == minutes);
        if (!(dateObj.getFullYear() == year && dateObj.getMonth() == month && dateObj.getDate() == day && dateObj.getHours() == hours && dateObj.getMinutes() == minutes)) {
            return "A megadott dátum idő nem létezik!";
        }
    },

    required: function (value) {
        if (empty(value)) {
            return "Kötelezően kitöltendő mező!";
        }
    },

    url: function (value) {
        if (empty(value)) return false;
        if (!/(((^https?)|(^ftp)):\/\/.*)/.test(value)) {
            return 'A mező webcímet tartalmazhat, melynek formátuma "http://www.weboldal.hu"';
        }
    },

    email: function (value) {
        if (empty(value)) return false;
        if (!/^(")?(?:[^\."\s])(?:(?:[\.])?(?:[\w\-!#$%&'*+/=?^_`{|}~]))*\1@(\w[\-\w]*\.){1,5}([A-Za-z]){2,6}$/.test(value)) {
            return 'A mező email címet tartalmazhat, melynek formátuma "felhasznalo@szolgaltato.hu"';
        }
    },

    interval: function () {
        //this az a field. Ebben az esetben lehet a dátum választó field
        var values = this.formGet(),
            value1, value2;
        for (var k in values) {
            if (k.includes('>')) value1 = values[k];
            if (k.includes('<')) value2 = values[k];
        }
        if (empty(value1)) return false;
        if (empty(value2)) return false;
        if (value1 > value2) {
            return 'Hibás intervallum';
        }
    },

    taxnumber: function (value) {
        if (empty(value)) return false;
        if (!/([0-9]{8})-([0-9]{1})-([0-9]{2})$/.test(value)) {
            return 'A mező adószámot tartalmazhat, melynek formátuma "12345678-1-12"';
        }
    },

    irsz: function (value) {
        if (empty(value)) return false;
        if (!/^[0-9]{4}$/.test(value)) {
            return 'A mező irányítószámot tartalmazhat, melynek formátuma "1234"';
        }
    },


    min: function (value, min, isText) {
        // dd(value, min);
        if (empty(value)) return false;
        if (isText) {
            if (value.length < min) {
                return 'A karakterek száma nem lehet kisebb mint: ' + min;
            }
        } else {
            if (value < min) {
                return 'A mező értéke nem lehet kisebb mint: ' + min;
            }
        }
    },

    max: function (value, max, isText) {
        // dd(value, max);
        if (empty(value)) return false;
        if (isText) {
            if (value.length > max) {
                return 'A karakterek száma nem lehet nagyobb mint: ' + max;
            }
        } else {
            if (value > max) {
                return 'A mező értéke nem lehet nagyobb mint: ' + max;
            }
        }
    },


};