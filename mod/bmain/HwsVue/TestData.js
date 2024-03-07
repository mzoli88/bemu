//regex generátor
import "./helpers/randexp.js";


global.FormFill = {

    run: false,

    doFormExport: function () {
        var grid = this.up("Grid");
        var fields;
        if (grid) {
            const create = {};
            const update = {};

            var i;

            for (i in grid.listCreate) {
                var v = grid.listCreate[i];
                create[v.name] = v;
            }
            for (i in grid.listUpdate) {
                var v = grid.listUpdate[i];
                update[v.name] = v;
            }

            var createKey = Object.keys(create);
            var updateKey = Object.keys(update);

            const result = [];
            createKey.forEach((item, idx) => {
                if (!~result.indexOf(item)) {
                    if (idx) {
                        const result_idx = result.indexOf(createKey[idx - 1]);
                        result.splice(result_idx + 1, 0, item);
                        return;
                    }
                    result.push(item);
                }
            });
            updateKey.forEach((item, idx) => {
                if (!~result.indexOf(item)) {
                    if (idx) {
                        const result_idx = result.indexOf(updateKey[idx - 1]);
                        result.splice(result_idx + 1, 0, item);
                        return;
                    }
                    result.push(item);
                }
            });

            fields = [];
            var out = [];
            for (i in result) {
                var st_create = null;
                var st_update = null;

                var rowcreate = create[result[i]];
                if (rowcreate) {
                    st_create = "";
                    for (let k in rowcreate)
                        if (
                            k != "sort" &&
                            k != "sort2" &&
                            k != "validation" &&
                            !empty(rowcreate[k])
                        )
                            st_create += " " + k + '="' + rowcreate[k] + '"';
                    if (
                        isString(rowcreate.validation) &&
                        rowcreate.validation.split("|").includes("required")
                    )
                        st_create += " required";
                    st_create += " />";
                }

                var rowupdate = update[result[i]];

                if (rowupdate) {
                    st_update = "";
                    for (let k in rowupdate)
                        if (
                            k != "sort" &&
                            k != "sort2" &&
                            k != "validation" &&
                            !empty(rowupdate[k])
                        )
                            st_update += " " + k + '="' + rowupdate[k] + '"';
                    if (
                        isString(rowupdate.validation) &&
                        rowupdate.validation.split("|").includes("required")
                    )
                        st_update += " required";
                    st_update += " />";
                }

                if (empty(st_create)) {
                    out.push('<Field v-if="!create"' + st_update);
                    continue;
                }
                if (empty(st_update)) {
                    out.push('<Field v-if="create"' + st_create);
                    continue;
                }

                if (st_create != st_update) {
                    out.push('<Field v-if="create"' + st_create);
                    out.push("<Field v-else" + st_update);
                    continue;
                }
                out.push("<Field" + st_create);
            }
            return dd(
                "<template>\n" +
                out.join("\n") +
                "\n</template>\n\n<script>\nexport default {\nprops: {\ncreate: Boolean,\n},\n}\n</" +
                "script>"
            );
        } else {
            fields = this.fields;
        }
        //szándékos ez a log, ne töröld ki
        dd(
            fields
                .map((x) => {
                    var out = "<Field";
                    for (let k in x)
                        if (
                            k != "sort" &&
                            k != "sort2" &&
                            k != "validation" &&
                            !empty(x[k])
                        )
                            out += " " + k + '="' + x[k] + '"';
                    if (x.validation.includes("required")) out += " required";
                    out += " />";
                    return out;
                })
                .join("\n")
        );
    },


    fillandSave: function (cmp) {
        var a = prompt("Hány rekordot generáljon?", "1");
        if (!isNumeric(a)) return;
        if (console.clear) console.clear();
        a = parseInt(a);
        this.doSaveLoop(cmp, a);
    },

    doSaveLoop: function (cmp, max, index) {
        if (max < index) return;
        index = index || 1;
        TestData.clearForm(cmp);
        this.fillData(cmp, x => {
            // dd(cmp.d);
            if (!cmp.formValidate()) {
                var errors = {};
                cmp.findFields({ errors: x => !empty(x) }, true).each(x => x.errors.every(y => errors[x.name] = y));
                console.error('Validációs hiba', errors, cmp.formGet());
                index++;
                this.doSaveLoop(cmp, max, index);
            } else {
                cmp.onSave(null, x => {
                    console.log(index + ' / ' + max + ' kész');
                    index++;
                    this.doSaveLoop(cmp, max, index);
                });
            }

        });

    },

    fillData: function (cmp, readyFn) {
        this.run = true;
        this.doFill(cmp, x => {
            cmp.$nextTick(x => {
                MajaxManager.isLoaded(x => {
                    this.doFill(cmp, x => {
                        cmp.$nextTick(x => {
                            MajaxManager.isLoaded(x => {
                                this.doFill(cmp, x => {
                                    cmp.$nextTick(x => {
                                        MajaxManager.isLoaded(x => {
                                            this.run = false;
                                            if (readyFn) readyFn();
                                        });
                                    });
                                });
                            });
                        });
                    });
                });
            });
        });
    },

    doFill: function (cmp, readyFn, fields) {
        var me = this;

        fields = fields || cmp.findFields(null, true);


        if (empty(fields)) return readyFn();

        var field = fields.shift();

        if (empty(field.getValue())) {
            me.fillField(field, x => this.doFill(cmp, readyFn, fields));
        } else {
            this.doFill(cmp, readyFn, fields);
        }

    },

    fillField: function (field, readyFn) {
        switch (field.type) {
            case 'string':
            case 'text':
            case 'textarea':
            case 'longtext':
                if (field.vReg) {
                    field.setValue(new RandExp(new RegExp(field.vReg)).gen());
                } else if (field.name == "name") {

                    //Ha a szülő Grid és van title-je
                    var Grid = field.up('Grid');
                    if (Grid) {

                        if (Grid.title) {
                            field.setValue(Grid.title + ' ' + TestData.randomString(10, false, true));
                        } else {
                            var colTitle = Grid.listableHeaders.find(x => x.name == 'name').title;
                            if (!empty(colTitle) && colTitle.toLowerCase() != 'megnevezés' && colTitle.toLowerCase() != 'név' && colTitle.toLowerCase() != 'neve') {
                                field.setValue(colTitle + ' ' + TestData.randomString(10, false, true));
                            } else {
                                // Ha a szülő Panel és van title - je
                                var Panel = Grid.up('Panel');
                                if (Panel && Panel.title) field.setValue(Panel.title + ' ' + TestData.randomString(10, false, true));
                                else {
                                    field.setValue(TestData.generateName());
                                }
                            }
                        }
                    }

                } else if (field.name.indexOf("neve") >= 0 || field.name.indexOf("nev") >= 0 || field.name.indexOf("user_name") >= 0 || field.label.toLowerCase().includes('neve') || field.label.toLowerCase().includes('név')) {
                    field.setValue(TestData.generateName());
                } else if (field.name === 'nationality' || field.name === "country") {
                    field.setValue('Magyar');
                } else if (field.name.indexOf("address") >= 0) {
                    field.setValue(TestData.randomVaros() + ", " + TestData.generateName() + " " + TestData.randomKozterulet() + " " + TestData.randomnumber(1, 2));
                } else if (field.name.indexOf("street") >= 0) {
                    field.setValue(TestData.generateName() + " " + TestData.randomKozterulet() + " " + TestData.randomnumber(1, 2));
                } else if (field.name.indexOf("irsz") >= 0 || field.validators.irsz || field.label.toLowerCase().includes('irányítószám')) {
                    field.setValue(new RandExp(new RegExp('^[0-9]{4}$')).gen());
                } else if (field.name.indexOf("city") >= 0 || field.name.indexOf("city_name") >= 0 || field.name.indexOf("varos") >= 0 || field.name.indexOf("birth_place") >= 0) {
                    field.setValue(TestData.randomVaros());
                } else if (field.name.indexOf("url") >= 0 || field.validators.url) {
                    field.setValue(TestData.generateUrl());
                } else if (field.name.indexOf("email") >= 0 || field.validators.email) {
                    field.setValue(TestData.generateEmail());
                } else if (field.name.indexOf("phone") >= 0 || field.validators.phone) {
                    field.setValue('+36' + TestData.getRandomInt(1000000, 99999999));
                } else if (field.name.indexOf("taxnumber") >= 0 || field.validators.taxnumber || field.label.toLowerCase().includes('adószám')) {
                    field.setValue(new RandExp(new RegExp('([1-9]{8})-([0-9]{1})-([0-9]{2})$')).gen());
                } else if (field.label.toLowerCase().includes('egyenleg')) {
                    field.setValue(TestData.getRandomInt(100, 99999999));
                } else if (field.label.toLowerCase().includes('számlaszám')) {
                    field.setValue(new RandExp(new RegExp('([0-9]{8})-([0-9]{8})-([0-9]{8})$')).gen());
                } else if (field.label.toLowerCase().includes('postafiók')) {
                    field.setValue(new RandExp(new RegExp('([0-9]{6})$')).gen());
                } else if (field.label.toLowerCase().includes('kerület')) {
                    field.setValue(new RandExp(new RegExp('([0-9]{2})$')).gen());
                } else if (field.label.toLowerCase().includes('épület')) {
                    field.setValue(new RandExp(new RegExp('([A-Z]{1})$')).gen() + ' épület');
                } else if (field.label.toLowerCase().includes('lépcsőház')) {
                    field.setValue(new RandExp(new RegExp('([A-Z]{1})$')).gen());
                } else if (field.label.toLowerCase().includes('szint')) {
                    field.setValue(new RandExp(new RegExp('([1-9]{1})$')).gen());
                } else if (field.label.toLowerCase().includes('ajtó')) {
                    field.setValue(new RandExp(new RegExp('([0-9]{2})$')).gen());
                } else if (field.label.toLowerCase().includes('adóazonosító')) {
                    field.setValue(new RandExp(new RegExp('([0-9]{10})$')).gen());
                } else if (field.label.toLowerCase().includes('személy azonosító')) {
                    field.setValue(new RandExp(new RegExp('([0-9]{10})$')).gen());
                } else if (field.label.toLowerCase().includes('házszám')) {
                    field.setValue(new RandExp(new RegExp('([0-9]{2})$')).gen());
                } else if (field.label.toLowerCase().includes('személyi igazolvány')) {
                    field.setValue(new RandExp(new RegExp('([0-9]{10})$')).gen());
                } else if (field.label.toLowerCase().includes('ksh')) {
                    field.setValue(new RandExp(new RegExp('([0-9]{8})-([0-9]{4})-([0-9]{3})-([0-9]{2})$')).gen());
                } else if (field.label.toLowerCase().includes('országkód')) {
                    field.setValue('HU');
                } else {
                    field.setValue(TestData.randomMondat());
                }

                break;
            case 'html':
                field.setValue('<b>' + TestData.randomMondat() + '</b><br><i>' + TestData.randomMondat() + '</i>');
                break;
            case 'multicombo':
                TestData.setComboValue(field, readyFn, true);
                return; // no ready 
                break;
            case 'combo':
                TestData.setComboValue(field, readyFn);
                return; // no ready 
                break;
            case 'radiogroup':
                field.setValue(TestData.getRandomValue(field.$refs.input.dinamicBox));
                break;
            case 'chgroup':
            case 'checkboxgroup':
                //megvárjuk, hogy betöltődjön a combo
                MajaxManager.isLoaded(x => {
                    field.$nextTick(x => {
                        if (field.$refs.input && !empty(field.$refs.input.dinamicBox)) field.setValue(TestData.getRandomValue(field.$refs.input.dinamicBox, true));
                        readyFn();
                    });
                });
                return; // no ready 
                break;
            case 'date':
                // field.setValue(me.randomDate(field.$attrs.min, field.$attrs.max));
                if (field.name.indexOf("birth") >= 0) {
                    field.setValue(TestData.randomDate());
                } else {
                    var min = new Date();
                    var max = new Date();
                    min.setDate(min.getDate() - 10);
                    max.setDate(max.getDate() + 10);
                    if (field.$attrs.time) {
                        var date = TestData.randomDate(min, max);
                        date.setMinutes(TestData.getRandomInt(0, 59));
                        date.setHours(TestData.getRandomInt(0, 23));
                        field.setValue(date);
                    } else {
                        field.setValue(TestData.randomDate(min, max));
                    }
                }
                break;
            case 'file':
                field.setValue(TestData.generateFile());
                break;
            case 'multifile':
                field.setValue([TestData.generateFile(), TestData.generateFile(), TestData.generateFile()]);
                break;
            case 'color':
                field.setValue(TestData.getRandomColor());
                break;
            case 'number':
            case 'integer':
                field.setValue(TestData.getRandomInt(1, 100));
                break;
            case 'slot':
                break;
            case 'cigennem':
            case 'rigennem':
            case 'rstatus':
            case 'cstatus':
                field.setValue(TestData.randomBool() ? 'I' : 'N');
                break;
            case 'checkbox':
                field.setValue(TestData.randomBool());
                break;
            default:
                console.log('%c WARN %c - nem kezelt mezőtípus ' + field.type + '/' + field.name, 'background: #222; color: #bada55', '');
                break;
        }
        readyFn();
    },

};

global.TestSort = {

    run: function (store, searchPanel) {
        if (console.clear) console.clear();
        var grid = searchPanel.up('Grid');

        grid.listableHeaders.filter(x => x.sortable != false).forEach(head => {

            store.sort(head.name, true).load('bigPage', 'noSearch', 'noCbEvents', 'debug', (s, d) => {
                if (!s) {
                    console.log('%c BAD %c - ASC ' + head.name, 'background: #222; color: red', '');
                    return false;
                }

                if (!this.isArraySorted(d.data, head.name)) {
                    console.log('%c BAD %c - ASC ' + head.name, 'background: #222; color: red', '');
                } else {
                    console.log('%c OK %c - ASC ' + head.name, 'background: #222; color: #bada55', '');
                }
            });

            store.sort(head.name, false).load('bigPage', 'noSearch', 'noCbEvents', 'debug', (s, d) => {
                if (!s) {
                    console.log('%c BAD %c - DES ' + head.name, 'background: #222; color: red', '');
                    return false;
                }

                if (!this.isArraySorted(d.data, head.name, true)) {
                    console.log('%c BAD %c - DES ' + head.name, 'background: #222; color: red', '');
                } else {
                    console.log('%c OK %c - DES ' + head.name, 'background: #222; color: #bada55', '');
                }

            });
        });
    },

    isArraySorted: function (arrIn, name, desc) {
        var arr = arrIn.map(x => {
            if (isString(x[name])) {
                x[name] = TestData.normalizeString(x[name]);
            }
            return x[name];
        });

        return arr.every(function (x, i) {
            if (i === 0) return true;

            var y = arr[i - 1];
            if (empty(x) || empty(y)) return true;

            if (desc) {
                if (isNumber(x) && isNumber(y)) return x <= y;
            } else {
                if (isNumber(x) && isNumber(y)) return x >= y;
            }
            if (!isString(x) || !isString(y)) return true;

            for (let index = 0; index < x.length; index++) {
                // const element = array[index];
                if (x[index] != y[index]) {
                    x = x[index];
                    y = y[index];
                    if (empty(x)) return true;
                    if (empty(y)) return true;
                    if (/[^A-Za-z0-9]+/.test(x)) return true;
                    if (/[^A-Za-z0-9]+/.test(y)) return true;
                    // dd(x, 11111, y);
                    if (desc) {
                        return x <= y || new Intl.Collator('hu').compare(y, x) >= 0;
                    } else {
                        // if (!(x >= y || new Intl.Collator('hu').compare(x, y) >= 0)) dd('error', x, y, arr[i - 1], arr[i]);
                        return x >= y || new Intl.Collator('hu').compare(x, y) >= 0;
                    }
                }
            }
            return true;
        });


    },
};

global.TestSearch = {

    run: function (store, searchPanel, onlyOne) {
        if (onlyOne) {
            onlyOne = prompt("Mező name tulajdonsága", sessionStorage.getItem("debug_lastSearchTest") ? sessionStorage.getItem("debug_lastSearchTest") : "name");
            if (empty(onlyOne)) {
                onlyOne = null;
            } else {
                sessionStorage.setItem("debug_lastSearchTest", onlyOne || 'name');
                onlyOne = { name: onlyOne };
            }
        }
        var a = prompt("Hány tesztet hajtsunk végre?", "4");
        if (!isNumeric(a)) return;
        if (console.clear) console.clear();
        a = parseInt(a);

        store.load('bigPage', 'noSearch', 'noCbEvents', 'debug', (s, d) => {
            if (empty(d.data)) {
                console.log('%c WARN %c - Nincs tesztelhető addat! ', 'background: #222; color: yellow', '');
                return;
            }
            searchPanel.findFields(onlyOne, true).each((field) => {
                if (field.up('Field')) field = field.up('Field');
                var values = TestData.suffleArray(TestData.normalizeArray(d.data, field.name));
                // dd(field.name, values);
                if (values.length == 0) {
                    console.log('%c WARN %c - ' + field.name + ' - Nincs tesztelhető addat! %c(' + field.label + ')%c ' + field.type, 'background: #222; color: yellow', '', 'background: yellow', '');
                    return;
                }
                var max = values.length < a ? values.length : a;
                this.searchOne(store, values, field, 0, max, TestData.suffleArray(TestData.normalizeArray(d.data, field.name)));
            });
        });
    },

    searchOne: function (store, values, field, index, max, AllValues) {
        if (max <= index || values.length == 0) return;
        index++;
        // dd(values);
        var value = values.shift();
        this.randomSearchValue(store, field, value, index, max, AllValues);
        this.searchOne(store, values, field, index, max, AllValues);
    },

    randomSearchValue: function (store, field, value, index, max, AllValues) {
        var obj = {};
        obj[field.name] = value;

        switch (field.type) {
            case 'number':
            case 'integer':
            case 'string':
            case 'text':
            case 'textarea':
            case 'longtext':
            case 'radiogroup':
                this.doSearch(store, field, obj, value, index, max);
                break;
            case 'combo':
                var find = [];
                find.push(value);

                this.findValueMulticombo(field, value, (v, data) => {
                    value = v;
                    find.push(value);
                    if (!empty(data)) {
                        data.filter(x => {
                            if (isArray(value)) return value.includes(x.value)
                            return value == x.value;
                        }).forEach(x => find.push(x.name));
                    }
                    // dd(value, find);
                    obj[field.name] = value;//újra fel kell tölteni, mert lehet változott
                    this.doSearch(store, field, obj, find, index, max);
                });
                break;
            case 'chgroup':
            case 'checkboxgroup':
                var find = [];

                if (isArray(value)) {
                    value = value.map(x => this.findValueChGroup(field, x));
                    value.forEach(x => find.push(x));
                } else {
                    value = this.findValueChGroup(field, value);
                    find.push(value);
                }

                if (field.$refs.input && !empty(field.$refs.input.dinamicBox)) {
                    field.$refs.input.dinamicBox.filter(x => {
                        if (isArray(value)) return value.includes(x.value)
                        return value == x.value;

                    }).forEach(x => find.push(x.name));
                }
                // dd(value, find);
                obj[field.name] = value;//újra fel kell tölteni, mert lehet változott

                this.doSearch(store, field, obj, find, index, max);

                //ha a value array akkor a multiple keresést már teszteltük
                if (AllValues.length && !isArray(value)) {
                    var obj2 = {};
                    find = [];

                    var values2 = TestData.getRandomArr(AllValues, true);
                    values2 = values2.map(x => this.findValueChGroup(field, x));
                    values2.forEach(x => find.push(x));
                    if (field.$refs.input && !empty(field.$refs.input.dinamicBox)) {
                        field.$refs.input.dinamicBox.filter(x => values2.includes(x.value)).forEach(x => find.push(x.name));
                    }
                    obj2[field.name] = values2;
                    // dd(values2, find);
                    this.doSearch(store, field, obj2, find, index, max, '###### multiple');
                }
                break;
            case 'multicombo':

                var find = [];

                if (isArray(value)) {
                    value.forEach(x => find.push(x));
                } else {
                    find.push(value);
                }

                this.findValueMulticombo(field, value, (v, data) => {
                    if (isArray(value)) {
                        value = v;
                        value.forEach(x => find.push(x));
                    } else {
                        value = v;
                        find.push(value);
                    }

                    if (!empty(data)) {
                        data.filter(x => {
                            if (isArray(value)) return value.includes(x.value)
                            return value == x.value;
                        }).forEach(x => find.push(x.name));
                    }
                    // dd(value, find);
                    obj[field.name] = value;//újra fel kell tölteni, mert lehet változott

                    this.doSearch(store, field, obj, find, index, max);
                });


                //ha a value array akkor a multiple keresést már teszteltük
                if (AllValues.length && !isArray(value)) {
                    var obj2 = {};
                    var values2 = TestData.getRandomArr(AllValues, true);
                    find = [];
                    values2.forEach(x => find.push(x));

                    this.findValueMulticombo(field, values2, (v, data) => {
                        values2 = v;
                        values2.forEach(x => find.push(x));
                        if (!empty(data)) {
                            data.filter(x => {
                                if (isArray(value)) return value.includes(x.value)
                                return value == x.value;
                            }).forEach(x => find.push(x.name));
                        }
                        obj2[field.name] = values2;
                        // dd(values2, find);
                        this.doSearch(store, field, obj2, find, index, max, '###### multiple');
                    });

                }
                break;
            case 'searchdate':
                value = value.split(' ').shift();
                obj[field.name] = value.replaceAll('.', '-').replace(/\-$/, '');
                this.doSearch(store, field, obj, value, index, max);

                var obj2 = {};
                obj2[field.name + '<'] = obj[field.name];
                obj2[field.name + '>'] = obj[field.name];
                this.doSearch(store, field, obj2, value, index, max, '###### date Interval');
                break;
            case 'date':
                value = value.split(' ').shift();
                obj[field.name] = value.replaceAll('.', '-').replace(/\-$/, '');
                this.doSearch(store, field, obj, value, index, max);
                break;
            case 'searchnumber':
                this.doSearch(store, field, obj, value, index, max);
                var obj2 = {};
                obj2[field.name + '<'] = value;
                obj2[field.name + '>'] = value;
                this.doSearch(store, field, obj2, value, index, max, '###### number Interval');
                break;
            case 'slot':
                break;
            case 'rigennem':
            case 'cigennem':
                var find;
                if (value.toLowerCase() == 'igen' || value.toLowerCase() == 'i') {
                    obj[field.name] = 'I';
                    find = ['Igen', 'I'];
                } else {
                    obj[field.name] = 'N';
                    find = ['Nem', 'N'];
                }
                this.doSearch(store, field, obj, find, index, max);
                var obj2 = {};
                obj2[field.name] = ['I', 'N'];
                this.doSearch(store, field, obj2, ['I', 'N', 'Igen', 'Nem'], index, max, '#### mindkettő');
                break;
            case 'rstatus':
            case 'cstatus':
                var find;
                if (value.toLowerCase() == 'aktív' || value.toLowerCase() == 'i') {
                    obj[field.name] = 'I';
                    find = ['Aktív', 'I'];
                } else {
                    obj[field.name] = 'N';
                    find = ['Inaktív', 'N'];
                }
                this.doSearch(store, field, obj, find, index, max);
                var obj2 = {};
                obj2[field.name] = ['I', 'N'];
                this.doSearch(store, field, obj2, ['I', 'N', 'Aktív', 'Inaktív'], index, max, '#### mindkettő');
                break;
            default:
                console.log('%c WARN %c - ' + index + '/' + max + ' - ' + field.name + ' - Nincs teszt eset lekezelve (' + field.label + ') ' + field.type, 'background: #222; color: yellow', '');

                break;
        }
    },

    findValueChGroup: function (field, text) {
        if (!isString(text)) return text;
        if (field.$refs.input && !empty(field.$refs.input.dinamicBox)) {
            var find = field.$refs.input.dinamicBox.filter(x => x.value == text || x.name == text);
            if (find.length) return find[0].value;
        }

        return text;
    },

    findValueMulticombo: function (field, text, readyFn) {
        if (isArray(text)) {
            if (text.filter(x => !isString(x)).length) return readyFn(text, []);
            var tmp = [];
            text.forEach(x => x.split(',').forEach(x => tmp.push(x)));
            text = tmp;
        } else {
            if (!isString(text)) return readyFn(text, []);
            text.split(', ')
        }

        if (field.store || field.$attrs.store) {
            getStore(field.store || field.$attrs.store).load('bigPage', 'noSearch', 'debug', { name: text }, (s, d) => {
                // dd(text, d)
                if (empty(d.data)) return readyFn(text, []);
                if (isArray(text)) {
                    readyFn(d.data.map(x => x.value), d.data);
                } else {
                    readyFn(d.data[0].value, d.data);
                }
            });
        }else{
            field.paramboxes.forEach(r=>{
                if(r.name == text)readyFn(r.value, r);
            });
        }
    },

    doSearch: function (store, field, search, find, index, max, extralog) {
        extralog = extralog || '';
        store.load('bigPage', 'noSearch', 'noCbEvents', 'debug', search, (s, d) => {
            if (!s || d.data.length == 0) {
                console.log('%c BAD %c - ' + index + '/' + max + ' - ' + field.name + ' search=' + find.toString() + ' %c(' + field.label + ')%c ' + field.type + ' ' + extralog, 'background: #222; color: red', '', 'background: yellow;', '');
                return false;
            }
            var notfound = d.data.filter(x => {
                let ch = TestData.normalizeString(x[field.name]);
                if (isArray(find)) {
                    return find.every(f => !ch.includes(TestData.normalizeString(f)));
                } else {
                    return !ch.includes(TestData.normalizeString(find));
                }
            }
            );
            if (notfound.length) {
                console.log('%c BAD %c - ' + index + '/' + max + ' - ' + field.name + ' search=' + find.toString() + ' %c(' + field.label + ')%c ' + field.type + ' ' + extralog + ' notfound:' + notfound.map(x => x[field.name]).toString(), 'background: #222; color: red', '', 'background: yellow;', '');
            } else {
                console.log('%c OK %c - ' + index + '/' + max + ' - ' + field.name + ' search=' + find.toString() + ' %c(' + field.label + ')%c ' + field.type + ' ' + extralog, 'background: #222; color: #bada55', '', 'background: yellow;', '');
            }

        });
    },


};

global.TestData = {


    tmComboData: {},


    clearForm: function (cmp) {
        cmp.findFields(null, true).each(function (field) {
            field.setValue(null);
            field.$nextTick(x => field.errors = []);
        });
    },


    normalizeString: function (st) {
        st = st || '';
        return st.toString()
            .toLowerCase();
    },

    getRandomValue: function (d, multiple) {
        if (empty(d)) return null;
        if (multiple) {
            const shuffled = d.sort(() => 0.5 - Math.random());
            let selected = shuffled.slice(0, this.getRandomInt(1, 4));
            return (selected.map(x => x.value));
        } else {
            return d[Math.floor(Math.random() * d.length)].value;
        }

    },

    normalizeArray: function (d, name) {
        if (empty(d)) return null;
        d = d.map(x => {
            if (name) x = x[name];
            if (isString(x)) x = x.trim();
            return x;
        });
        d = d.filter(x => !empty(x));
        return [...new Set(d)]
    },

    suffleArray: function (d) {
        return d.sort(() => 0.5 - Math.random())
    },

    getRandomArr: function (d, multiple, name) {
        d = this.normalizeArray(d, name);
        if (multiple) {
            const shuffled = d.sort(() => 0.5 - Math.random());
            let selected = shuffled.slice(0, this.getRandomInt(1, 4));
            return selected;
        } else {
            return d[Math.floor(Math.random() * d.length)];
        }

    },

    setComboValue: function (combo, readyFn, multiple) {

        var me = this,
            input = combo.$refs.input;

        if (this.tmComboData[combo.name]) {
            //ha van perent-je akkor újra kell tölteni a gyereket
            var children = combo.up().findFields({ parent: combo.name }, false);
            if (!empty(children)) {
                children.each(function (ch) {
                    if (me.tmComboData[ch.name]) delete me.tmComboData[ch.name];
                });
            }

            combo.setValue(me.getRandomValue(this.tmComboData[combo.name], multiple));
            readyFn();
            return;
        }

        if (combo.$attrs.store) {
            var st = getStore(combo.$attrs.store),
                query = {
                    'per-page': 500,
                    'page': 1,
                };

            if (combo.$attrs.parent) {
                var parent = combo.up().down({ name: combo.$attrs.parent });
                if (parent) {
                    let parent_id = parent.getValue();
                    if (parent_id) {
                        query.parent_id = parent_id;
                    } else {
                        return null;
                    }
                }
            }

            st.load('bigPage', 'noSearch', 'debug', query, function (s, d) {
                if (!s) return;
                if (isObject(d) && d.data) d = d.data;
                me.tmComboData[combo.name] = d;
                combo.setValue(me.getRandomValue(d, multiple));
                readyFn();
            });
        } else {
            combo.setValue(me.getRandomValue(input.dinamicBox, multiple));
            readyFn();
        }
    },

    generateFile: function () {
        let ct = this.randomMondat();
        return {
            file_content: Base64.encode(ct),
            name: this.randomString(20) + '.txt',
            size: ct.length,
            type: "text/plain",
        };
    },

    getRandomColor: function () {
        var letters = '0123456789ABCDEF';
        var color = '#';
        for (var i = 0; i < 6; i++) {
            color += letters[Math.floor(Math.random() * 16)];
        }
        return color;
    },

    randomBool: function () {
        return Math.random() >= 0.5;
    },

    generateEmail: function () {
        var kisz = this.data.emailSzolgaltato[Math.floor(Math.random() * this.data.emailSzolgaltato.length)],
            name = this.generateName(true);
        return name + this.randomnumber(0, 4) + "@teszt." + kisz;
    },

    randomString: function (maxNumber, allowSpace, alllowEkezet) {
        maxNumber = maxNumber || 100;
        var possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
        if (allowSpace) possible += "   ";
        if (alllowEkezet) possible += "őŐűŰíÍéÉáÁóÓúÚöÖ";
        var text = "";
        for (var i = 0; i < Math.floor(Math.random() * maxNumber) + 4; i++)text += possible.charAt(Math.floor(Math.random() * possible.length));
        return text;
    },

    randomMondat: function (min, max) {
        var kiv = this.data.mondatok[Math.floor(Math.random() * this.data.mondatok.length)];
        if (kiv.length > max) kiv = kiv.substr(0, max);
        return kiv;

    },

    randomDate: function (min, max) {
        min = min || new Date(1955, 0, 1);
        max = max || new Date(1902 + new Date().getYear(), 0, 1);
        min = min.getTime();
        max = max.getTime();
        return new Date(this.getRandomInt(min, max));
    },

    getRandomInt: function (min, max) {
        return Math.floor(Math.random() * (max - min + 1)) + min;
    },

    randomKozterulet: function () {
        return this.data.kozterulet[Math.floor(Math.random() * this.data.kozterulet.length)];
    },

    randomnumber: function (min, max) {
        var possible = "0123456789";
        var text = "";
        for (var i = 0; i < (Math.floor(Math.random() * (max - min)) + min) + 1; i++) {
            text += possible.charAt(Math.floor(Math.random() * possible.length));
            if (i === 0 && text === '0') text = 1;
        }
        return parseInt(text);
    },

    randomVaros: function () {
        return this.data.varosok[Math.floor(Math.random() * this.data.varosok.length)];
    },

    generateName: function (noSpec) {
        var nem = this.randomBool() ? 'f' : 'n',
            keresztnev = this.data.keresztNevek[nem][Math.floor(Math.random() * this.data.keresztNevek[nem].length)],
            vezeteknev = this.data.vezetekNevek[Math.floor(Math.random() * this.data.vezetekNevek.length)];
        if (noSpec) return this.replaceEkezet(vezeteknev + keresztnev).toLowerCase();
        return vezeteknev + " " + keresztnev;
    },

    generateUrl: function (noSpec) {
        var out = 'http';
        if (this.randomBool()) out += 's';
        out += '://';
        out += this.generateName(true);
        out += '.' + this.data.webN[Math.floor(Math.random() * this.data.webN.length)];
        return out;
    },

    replaceEkezet: function (input) {
        var accents = 'áéíóöőúüűÁÉÍÓÖŐÚÜŰ';
        var replace = 'aeiooouuuAEIOOOUUU';
        accents = accents.split('');
        replace = replace.split('');
        var textLength = input.length;
        var text = '';
        for (var i = 0; i < textLength; i++) {
            var pos = accents.indexOf(input[i]);
            if (pos > -1) {
                text += replace[pos];
            } else {
                text += input[i];
            }
        }
        return text;
    },

    data: {

        emailSzolgaltato: [
            "freemail.hu",
            "gmail.com",
            "citromail.hu",
            "yahoo.com"
        ],

        keresztNevek: {
            f: [
                "László",
                "István",
                "József",
                "János",
                "Zoltán",
                "Sándor",
                "Gábor",
                "Ferenc",
                "Attila",
                "Péter",
                "Tamás",
                "Zsolt",
                "Tibor",
                "András",
                "Csaba",
                "Imre",
                "Lajos",
                "György",
                "Balázs",
                "Gyula",
                "Mihály",
                "Károly",
                "Róbert",
                "Béla",
                "Dávid",
                "Dániel",
                "Ádám",
                "Krisztián",
                "Miklós",
                "Norbert",
                "Bence",
                "Máté",
                "Pál",
                "Szabolcs",
                "Roland",
                "Gergő",
                "Antal",
                "Bálint",
                "Richárd",
                "Márk",
                "Levente",
                "Gergely",
                "Ákos",
                "Viktor",
                "Árpád",
                "Géza",
                "Márton",
                "Kristóf",
                "Jenő",
                "Kálmán",
                "Patrik",
                "Martin",
                "Milán",
                "Barnabás",
                "Dominik",
                "Marcell",
                "Ernő",
                "Mátyás",
                "Endre",
                "Áron",
                "Dezső",
                "Botond",
                "Nándor",
                "Zsombor",
                "Szilárd",
                "Erik",
                "Olivér",
                "Alex",
                "Vilmos",
                "Ottó",
                "Benedek",
                "Dénes",
                "Kornél",
                "Bertalan",
                "Benjámin",
                "Zalán",
                "Kevin",
                "Adrián",
                "Rudolf",
                "Albert",
                "Vince",
                "Ervin",
                "Győző",
                "Zsigmond",
                "Andor",
                "Gusztáv",
                "Szilveszter",
                "Iván",
                "Noel",
                "Barna",
                "Elemér",
                "Arnold",
                "Csongor",
                "Ábel",
                "Krisztofer",
                "Emil",
                "Tivadar",
                "Hunor",
                "Bendegúz",
                "Henrik",
                "Zétény"
            ],
            n: [
                "Mária",
                "Erzsébet",
                "Katalin",
                "Ilona",
                "Éva",
                "Anna",
                "Zsuzsanna",
                "Margit",
                "Judit",
                "Ágnes",
                "Julianna",
                "Andrea",
                "Ildikó",
                "Erika",
                "Krisztina",
                "Irén",
                "Eszter",
                "Magdolna",
                "Mónika",
                "Edit",
                "Gabriella",
                "Szilvia",
                "Anita",
                "Anikó",
                "Viktória",
                "Márta",
                "Rozália",
                "Tímea",
                "Piroska",
                "Ibolya",
                "Klára",
                "Tünde",
                "Dóra",
                "Zsófia",
                "Gizella",
                "Veronika",
                "Alexandra",
                "Csilla",
                "Terézia",
                "Nikolett",
                "Melinda",
                "Adrienn",
                "Réka",
                "Beáta",
                "Marianna",
                "Nóra",
                "Renáta",
                "Vivien",
                "Barbara",
                "Enikő",
                "Bernadett",
                "Rita",
                "Brigitta",
                "Edina",
                "Hajnalka",
                "Gyöngyi",
                "Jolán",
                "Petra",
                "Orsolya",
                "Etelka",
                "Boglárka",
                "Borbála",
                "Noémi",
                "Valéria",
                "Teréz",
                "Annamária",
                "Fanni",
                "Kitti",
                "Nikoletta",
                "Emese",
                "Aranka",
                "Laura",
                "Lilla",
                "Róza",
                "Klaudia",
                "Anett",
                "Kinga",
                "Zita",
                "Beatrix",
                "Zsanett",
                "Rózsa",
                "Emma",
                "Dorina",
                "Hanna",
                "Lili",
                "Sára",
                "Irma",
                "Bianka",
                "Júlia",
                "Györgyi",
                "Henrietta",
                "Diána",
                "Luca",
                "Mariann",
                "Bettina",
                "Dorottya",
                "Virág",
                "Jázmin",
                "Sarolta",
                "Evelin"
            ]
        },

        vezetekNevek: [
            "Nagy",
            "Kovács",
            "Tóth",
            "Szabó",
            "Horváth",
            "Varga",
            "Kiss",
            "Molnár",
            "Németh",
            "Farkas",
            "Balogh",
            "Papp",
            "Takács",
            "Juhász",
            "Lakatos",
            "Mészáros",
            "Oláh",
            "Simon",
            "Rácz",
            "Fekete",
            "Szilágyi",
            "Török",
            "Fehér",
            "Balázs",
            "Gál",
            "Kis",
            "Szűcs",
            "Kocsis",
            "Pintér",
            "Fodor",
            "Orsós",
            "Szalai",
            "Sipos",
            "Magyar",
            "Lukács",
            "Gulyás",
            "Bíró",
            "Király",
            "Katona",
            "László",
            "Jakab",
            "Bogdán",
            "Balog",
            "Sándor",
            "Boros",
            "Fazekas",
            "Kelemen",
            "Antal",
            "Somogyi",
            "Váradi",
            "Fülöp",
            "Orosz",
            "Vincze",
            "Veres",
            "Hegedűs",
            "Deák",
            "Budai",
            "Pap",
            "Bálint",
            "Pál",
            "Illés",
            "Szőke",
            "Vörös",
            "Vass",
            "Bognár",
            "Lengyel",
            "Fábián",
            "Bodnár",
            "Szücs",
            "Hajdu",
            "Halász",
            "Jónás",
            "Kozma",
            "Máté",
            "Székely",
            "Gáspár",
            "Pásztor",
            "Bakos",
            "Dudás",
            "Major",
            "Hegedüs",
            "Virág",
            "Orbán",
            "Novák",
            "Barna",
            "Soós",
            "Nemes",
            "Tamás",
            "Pataki",
            "Faragó",
            "Balla",
            "Borbély",
            "Kerekes",
            "Szekeres",
            "Barta",
            "Péter",
            "Csonka",
            "Mezei",
            "Dobos",
            "Márton",
            "Sárközi",
            "Megyik"
        ],

        mondatok: [
            "Egy alma és egy táska.",
            "(Ő) a bátyám.",
            "Van egy fényképezőgépem.",
            "Itt van egy gyerek.",
            "Gyerekek vannak itt.",
            "Az országunk szép.",
            "Ez a(z ő) lánya.",
            "Ez a(z én) szótáram.",
            "Ők egy orvos és egy tanár.",
            "Van egy narancssárga borítékom.",
            "Jó estét.",
            "Ez az első (kereszt)nevem.",
            "Van egy lakásom és egy házam.",
            "Ez egy nemzetközi újság.",
            "Jó állásom van.",
            "Van két kulcsom.",
            "Az angol egy szép nyelv.",
            "Angolt tanulok.",
            "Van egy levelem és egy képes újságom.",
            "Ebben a városban lakom.",
            "Ez egy jó magazin.",
            "Van egy térképem a jegyzetfüzetemben.",
            "Házas vagyok és van két gyerekem.",
            "Két nevem van.",
            "(Ők) kedves emberek.",
            "Ez egy képeslap a bőröndömben.",
            "Ő a nővérem fia.",
            "Magyarország déli részén lakom.",
            "Ez egy bélyeg a borítékomon.",
            "(Én egy) Diák vagyok.",
            "Amerikában akarok élni.",
            "Kávét akarok vagy teát.",
            "Ez egy nagy könyv.",
            "Van egy kis csészém.",
            "Sonkás szendvicset akarok.",
            "Forró kávét iszom.",
            "A bátyámnak van egy új barátnője.",
            "A nagymamám és a nagypapám idősek.",
            "Hideg teát akarok inni.",
            "A nagynéném Franciaországban él.",
            "Angolt tanulni könnyű.",
            "A narancs ennivaló.",
            "Szép otthonom van.",
            "Tejet inni jó.",
            "Van egy parkom otthon.",
            "Ez egy szép város.",
            "A feleségem fiatal.",
            "A munkám kellemes.",
            "Van egy olcsó lakásom..",
            "Kávét vagy teát iszok reggel.",
            "Este boldog vagyok.",
            "Van két aranyos unokahúgom.",
            "Az unokaöcsémnek három telefonja van.",
            "A tanáromnak van egy fotója a bőröndjében.",
            "A házam kicsi és barátságos.",
            "Ma szerda van.",
            "A nagybácsikám egy kedves orvos és Németországban él.",
            "Levelet írok.",
            "Van egy sajtos szendvicsem.",
            "Nagy családom van.",
            "Az apám ötvenkét éves.",
            "A szüleim kedvesek.",
            "Az anyám és az apám idősek.",
            "Van egy unokahúgom és egy unokaöcsém.",
            "Kilencszáz egy nagy szám.",
            "A címem Virág utca, 8.",
            "Szeretem a roston sült csirkét.",
            "A szabadságom 28 nap.",
            "A férje egy szörnyű ember.",
            "Szeretek ásványvizet inni.",
            "Ez az időjárás szörnyű.",
            "Szeretem a fagylajtot.",
            "Szép időt akarok.",
            "A nővéremnek van egy új barátja.",
            "A \"számológép\" egy nehéz szó.",
            "Az autóm drága.",
            "Megértelek téged.",
            "Ki a kedvenc színészed?",
            "Mit csinálsz délután?",
            "Mi 5-kor fogunk megérkezni.",
            "A pék kenyeret készít.",
            "Fázok, mert hideg van.",
            "Szeretem a vajas kenyeret.",
            "Vékony/Sovány vagyok, de erős.",
            "Szeretnék egy új autót.",
            "Nem kaptam el a labdát.",
            "El kell érnünk egy vonatot.",
            "Természetesen szeretem a mákos tésztát.",
            "Mikor jössz és nézel / látogatsz meg minket?",
            "Mibe/Mennyibe kerül?",
            "Szeretnék (meg)tanulni vezetni.",
            "Este nagyon jó ötletem/gondolataim vannak.",
            "Van néhány jó filmem.",
            "Egy repülő repül ott.",
            "Soha nem megyek / járok focimeccs(ek)re.",
            "Szerencsére ismerem őt.",
            "Én egy közeli iskolába járok.",
            "Van egy nagyon jó fodrászom.",
            "Szeretném megenni a süti/torta felét.",
            "Öt napot voltam kórházban.",
            "Neki két órája van ideérni. / arra, hogy ideérjen.",
            "Ismerek egy nagyon jó tolmácsot.",
            "Nem szeretem az újságírókat.",
            "Az út hazafelé hosszú.",
            "Ő elindul az iskolába 6-kor.",
            "Ő kedvel/szeret minket.",
            "Ő gondosan vigyáz/figyel a fiamra.",
            "Szeretem a fiúmat.",
            "Tudsz teát készíteni?",
            "Szükségem van egy autószerelőre.",
            "Ők megjavították az autómat.",
            "Szeretnéd meglátogatni a kedvenc hegyemet?",
            "Találkoztam néhány nővérrel/ápolóval.",
            "A pilótánk rosszul érzi magát.",
            "Láttál már (valaha) egy nagy repülőt?",
            "Mit szeretsz játszani?",
            "Beszélnem kell a recepcióssal/portással.",
            "Szeretném látni a tengert.",
            "Látod azt a szép virágot?",
            "Eladom a gyűrűm.",
            "Hányszor/Hány alkalommal mégy boltba egy héten/hetente?",
            "Nem akarok boltos lenni.",
            "Ő egy népszerű énekes.",
            "Beszélj lassan, kérlek.",
            "A nyár itt van hamar(osan).",
            "Vedd le a kabátodat.",
            "A taxisofőrök sokat dolgoznak.",
            "Ő hétvégente/hétévégéken tanít engem.",
            "Én mindig fáradt vagyok.",
            "A vonat elment.",
            "Én egy faluban voltam gyerek.",
            "Szeretek éjjel sétálni.",
            "A télnek vége (van).",
            "Az ősz szeles és felhős.",
            "Ez a gép rossz.",
            "Mikor szoktál/szoktatok elmenni strandra?",
            "Van egy kényelmes ágyam.",
            "Hozz nekem egy kis vizet!",
            "A táskám barna.",
            "Tudsz kártyázni?",
            "Tudunk most csevegni?/Cseveghetünk most?",
            "Szeretem az élénk/erős színeket.",
            "Van számítógéped?",
            "Szeretek a barátaimmal főzni.",
            "Szeretsz keresztrejtvényt fejteni?",
            "Mikor szoktál táncolni?",
            "Én mindig nagyot vacsorázok.",
            "Mit szeretsz enni?",
            "Ez a történet izgalmas.",
            "A kedvenc napom a szerda.",
            "Nem tudok halat fogni.",
            "Mikor szoktál virágot kapni?",
            "Hány barátod van?",
            "Ez nem a kedvenc játékom.",
            "Nem szeretek korán kelni.",
            "Nekem kell elmennem (be)vásárolni?",
            "Két évvel ezelőtt mentem/voltam úszni.",
            "Mi a fenét(poklot) csinálsz itt?",
            "Kellene, hogy legyen hobbim.",
            "Még sohasem próbáltam (ki) a jégkorcsolyázást.",
            "Interjúvoltak már meg téged valaha?/Készítettek már veled valaha interjút?",
            "Mit tudsz róla/arról/erről?",
            "Nem akarom hogy (te) késs.",
            "Meghallgatnál/(Ide)Figyelnél rám, kérlek?",
            "Milyen hosszú lesz?",
            "Egy hónap négy hét, de nem mindig.",
            "Nem a közelben lakom.",
            "Soha nem iszom alkoholt, amikor vezetek.",
            "Szeretnék egy saját irodában dolgozni.",
            "Gyakran csinálod ezt?",
            "Tetszik ez a festmény, de azt hiszem túl drága.",
            "A sörözők ebben a városban zsúfoltak és zajosak.",
            "A vér vörös a vas miatt.",
            "Nyugi! Ne csináld ezt (így)!",
            "Nagyon szeles az idő most – menjünk vitorlázni!",
            "A rövid út erre van.",
            "Ne dohányozz itt – gyerekek vannak itt.",
            "Néha szabad vagyok.",
            "Ez az egyik a kedvenc dalaim közül.",
            "Ez a tavasz eddig nagyon meleg volt.",
            "Kezd/Indulj (el) most!",
            "Nem tudok sokáig maradni.",
            "És aztán/akkor hirtelen megláttam őt.",
            "Mit fogsz csinálni nyáron?",
            "Szeretsz napozni?",
            "Mivel csinálsz fotókat?",
            "A forgalom nagyon nagy (=forgalmas) itt.",
            "Hol vetted ezt a fát?",
            "Én általában nem szoktam ezt a kérdést feltenni / megkérdezni.",
            "Te nem fogsz engem meglátogatni az idén, ugye?",
            "Tudsz röplabdázni?",
            "Ezt nézd/figyeld!",
            "Esik és én eláztam / vizes vagyok.",
            "Azt hiszem soha nem fogom kipróbálni a /szél/szörfözést.",
            "Ez a tojás nem sárga – ez probléma?",
            "Szomjas vagyok és éhes is.",
            "Szeretnék egy nagyon kényelmes karosszéket/fotelt a szobámba(n).",
            "Télen szeretek meleg/forró fürdőt venni.",
            "Nem tudok bemenni a fürdőszobába.",
            "A hálószobám a padláson van.",
            "Tudod, hogy mi van mögötted?",
            "A szobámban a szőnyeg szürke.",
            "Mikor voltál utoljára a patikában?",
            "Meg kell tisztítanom az ablakomat mert nem tiszta.",
            "Ébresztő órára ébredsz fel?",
            "Nincs túl sok ruhám.",
            "Meg kell javítanunk a tűzhelyünket.",
            "Ezek a konyhaszekrények jók lesznek?",
            "Mi ez a rendetlenség az íróasztalodon?",
            "Soha nem lesz szükségünk mosogatógépre.",
            "A kutyák nem szeretnek engem – ez kölcsönös.",
            "Nagyon boldog voltam a hétvégén.",
            "Mindenki tudja /ezt/.",
            "A barátom szeretne híres lenni.",
            "Érzel füstszagot is?/Te is érzed a füst szagát?",
            "Nem találok ennivalót a hűtőben.",
            "(Mi) A kutyá(nka)t a kert(ünk)ben tartjuk.",
            "(Ez) Fontos?",
            "A nyúl a ketrec/kalitka előtt van.",
            "Nincsenek királyok Magyarországon.",
            "Milyen gyakran vagy a konyhá-d/-tokban?",
            "Nagyon futurisztikus lámpád van.",
            "Mi van a bal zsebedben?",
            "Tagja vagy a könyvtárnak?",
            "Az apám úgy néz ki, mint Bartez.",
            "A nappalink nagy.",
            "A főétkezésem a vacsora.",
            "Nincs tükör a szobámban.",
            "Az én rádiós-asztalilámpás-ébresztőórám nagyon modern.",
            "Ismered (=tudod) a legfrissebb (=legutolsó) híreket?",
            "Mi nem egy újságosnál vesszük az újságjainkat.",
            "Én nem tudom, ki lakik mellettünk.",
            "A számítógépem az íróasztalomon van.",
            "Van más ötleted?",
            "Még nem voltam nagy kastélyban.",
            "Van piros tollad?",
            "6 kép van a falunkon.",
            "Ez egy nagyszerű hely nyáron.",
            "Imádom a növényeket az otthonomban.",
            "Nem törtem még el sok tányért.",
            "Voltál már valaha a rendőrségen?",
            "Valaki tett egy csomagot a postaládámba.",
            "Minden héten el kell mennem a postára.",
            "Szeretnék egy vadonatúj rádiót.",
            "Az utca jobb oldalán lakunk.",
            "Többet kellene aludnom.",
            "Van egy díványunk a tv-vel szemben.",
            "Kaptam egy rádiót a születésnapomra.",
            "Szeretne egy úszómedencét a kertjében.",
            "A macskám szeret az asztalomon ülni.",
            "Mindenki szeret beszélni.",
            "Hol van a legközelebbi vécé? … Gyorsan!",
            "A falaink vékonyak.",
            "A mosógépünk a tűzhely és a mosogatógép között van.",
            "Én dolgozok amíg / mialatt ő alszik.",
            "Az egész napom nagyon elfoglalt/forgalmas/nyüzsgő.",
            "Szeretnék egy nagyobb ablakot.",
            "Mit csináltál (már) megint?",
            "Hol van az érkezési oldal (csarnok)?",
            "Meg kell találnom a csomagfelvételt.",
            "A biciklim eltűnt.",
            "A haja fekete.",
            "Kérem a beszállókártyát!",
            "Nem szeretem az unalmas órákat.",
            "Ebben a városban születtem.",
            "Hol és mikor születtél?",
            "Az a film remek/kitűnő volt.",
            "Ismered az új autóversenyző bajnokot?",
            "Ellenőrizd a hőmérsékletet odakint.",
            "Mond meg nekem, hogy hol van a bejelentkező pult.",
            "Meg fogom azt venni, így szükségem van a bankkártyámra.",
            "Szeretnél sakkozni velem?",
            "Számolj vissza(felé)!",
            "Nos, hagyjuk abba ezt a beszélgetést most.",
            "A falaid nagyon sötétek a szobádban.",
            "Ő még nem érkezett meg; a gépe/ járata késik.",
            "Meg kell találnunk őt a tranzitváróban.",
            "Mi az úti célod?",
            "A véleményem különbözik.",
            "Rajzoljunk ide valamit!",
            "Van valami a szememben.",
            "Mikor van a (repülő)járatunk?",
            "Nem tudok válaszolni neked erre a kérdésre folyékonyan.",
            "Ki az az ember (férfi) a kapunál?",
            "Ő nagyon okos, de nem egy zseni.",
            "A te / Az ön kézitáskája nagyon nehéz, … hagyj / hagyjon valamit otthon.",
            "Hallasz engem ebben a zajban?",
            "Mikor fogunk landolni/leszállni?",
            "Mi lenne az utolsó kívánságod?",
            "Mit csináltál tavaly Karácsonykor?",
            "Mennyit kerestél a múlt hónapban?",
            "Ízlett az ebéded?",
            "Jársz focimeccsekre?",
            "Sok húst tartunk a fagyasztónkban.",
            "A barátom orvoslást / orvosnak akart tanulni.",
            "Mi történt az orroddal?",
            "Mire gondolsz / Min gondolkodsz most?",
            "Minden évben tartasz szülinapi bulit?",
            "Megállítottak minket az útlevél ellenőrzésnél.",
            "Volt egy gyönyörű zongorám, de a végén / végül eladtam.",
            "Ki a kedvenc játékosod?",
            "Ezt kell gyakorolnom egész nap.",
            "Lovagoltál már pónin?",
            "Használd a biztonsági övedet, kérlek!",
            "Érzed ezt a szagot vagy valami(-) (ilyesmi)-t?",
            "Egész héten tanulnom kell, úgyhogy / így nem tudok veled elmenni szórakozni.",
            "A tinédzserek 13 és 19 év közötti fiatal emberek.",
            "Mit gondolsz erről a problémáról?",
            "Le tudod ezt nekem fordítani?",
            "Vegyél (el) egy sütit a tálcámról!",
            "Nem tudok gépelni ilyen típusú billentyűzettel.",
            "Senki sem jut(hat) be az éjszakai klubba 18 éves kor alatt.",
            "Van egy bomba a széked alatt – ne mozdulj!",
            "Nincs jó program / műsor ma este 9-ig.",
            "Ha tudod ezt használni, magyarázd el nekem, kérlek!",
            "Nem szeretek hosszú nadrágot hordani nyáron.",
            "Ez nem az én évem volt.",
            "Tegnap fáradt voltam, de sokat aludtam és most olyan fitt vagyok, mint rendesen / általában.",
            "Voltál már valaha külföldön?",
            "Ne légy csecsemő!",
            "Mivé akarsz válni?",
            "Mit csinálsz, mielőtt lefekszel?",
            "Kölcsön kérhetem az esernyődet?",
            "Van valami az üvegedben?",
            "Mit fogunk venni?",
            "Van apród?",
            "Nekem tetszik az a szereplő. Neked?",
            "A legtöbb ember nagyon szereti a Karácsonyt.",
            "Ki kell takarítanom a házat.",
            "Tudod, hogy ki az ő titkára?",
            "Nem szeretem, amikor tartozásom / adósságom van.",
            "Nem értem az ő ötletének a leírását.",
            "Mikor halt meg?",
            "Mennyit szeretnél keresni?",
            "Közel a / Nemsokára Húsvét, de én nem várom.",
            "Mit élvezel a legjobban a nyárban?",
            "Szeretem a nyarat, különösen a napsütést és azt, hogy nincs hideg.",
            "Mi volt a legkedvesebb / legkellemesebb élményed / tapasztalatod a munkádban?",
            "Egy műhelyben dolgozom, de az nem egy gyárban van.",
            "Hol tudlak megtalálni / talállak meg nyáron?",
            "Mikor fogsz végezni?",
            "Mit akarsz kapni?",
            "Meg tudod adni nekem az irányt?",
            "A nyaralásunk / szabadságunk nagyszerű volt.",
            "M.C. egy nagyszerű író.",
            "Az a teszt nagyon nehéz volt.",
            "Mit utálsz a legjobban?",
            "Gyere ide, azonnal!",
            "Megcsókoltam és (aztán) elmentem.",
            "Mit akarsz enni később?",
            "Van élet a Földön, de nincs élet a Holdon.",
            "Hol veszítetted el a sapkád?",
            "Ők 2000-ben házasodtak.",
            "Hamarosan el kell költöznünk.",
            "Mikor jöttél haza (az el)múlt éjjel?",
            "Sose olvasok novellákat / regényeket.",
            "Nincs kedvenc regényíróm.",
            "Elmúltam 30. / Túl vagyok a 30-on. / 30 fölött(i) vagyok.",
            "A nagypapám több mint 90 éves.",
            "Az elmúlt pár év nehéz volt.",
            "Nem érdekel a politika, de néha megértem.",
            "Ebben az országban sok gazdag és szegény ember van.",
            "Nem olyan / annyira jó népszerűnek lenni.",
            "Adtam ajándékot mindenkinek.",
            "Ismerek néhány csinos lányt ott.",
            "Soha nem látogattam még meg börtönt.",
            "Valószínűleg hamarosan esni fog.",
            "Láttál már valaha egy igazi sztárt?",
            "Miért nem emlékszel rá?",
            "Mikor akarsz visszavonulni / nyugdíjba menni?",
            "Gazdag vagyok, de nem akarok semmit sem venni.",
            "Nem akarod ezt eladni nekem?",
            "Miért küldted őt oda?",
            "Szükségem van erre az új szoftverre.",
            "El akarod költeni ma az összes pénzedet?",
            "Még itt vagyok.",
            "Mindenki sikeres akar lenni.",
            "Gondolkodtam és hirtelen megtaláltam a megoldást.",
            "Holnap szabadabb leszek.",
            "Soha nem voltam még munkanélküli.",
            "A videónkra ráférne a kitisztítás.",
            "A háború befejeződött.",
            "Az esküvőjük szép volt.",
            "Nem nyerhetsz mindig.",
            "Az időjárás csodálatos a tengernél."
        ],

        webN: [
            'hu',
            'com',
            'net'
        ],

        kozterulet: [
            "dűlő",
            "dűlőút",
            "erdősor",
            "fasor",
            "körút",
            "köz",
            "lakótelep",
            "liget",
            "park",
            "sétány",
            "sor",
            "sugárút",
            "tér",
            "út",
            "utca"
        ],

        varosok: [
            "Aba",
            "Abádszalók",
            "Abaújszántó",
            "Abony",
            "Ács",
            "Adony",
            "Ajak",
            "Ajka",
            "Albertirsa",
            "Alsózsolca",
            "Aszód",
            "Bábolna",
            "Bácsalmás",
            "Badacsonytomaj",
            "Baja",
            "Baktalórántháza",
            "Balassagyarmat",
            "Balatonalmádi",
            "Balatonboglár",
            "Balatonföldvár",
            "Balatonfüred",
            "Balatonfűzfő",
            "Balatonkenese",
            "Balatonlelle",
            "Balkány",
            "Balmazújváros",
            "Barcs",
            "Bátaszék",
            "Bátonyterenye",
            "Battonya",
            "Békés",
            "Békéscsaba",
            "Bélapátfalva",
            "Beled",
            "Berettyóújfalu",
            "Berhida",
            "Besenyszög",
            "Biatorbágy",
            "Bicske",
            "Biharkeresztes",
            "Bodajk",
            "Bóly",
            "Bonyhád",
            "Borsodnádasd",
            "Budakalász",
            "Budakeszi",
            "Budaörs",
            "Budapest",
            "Bük",
            "Cegléd",
            "Celldömölk",
            "Cigánd",
            "Csákvár",
            "Csanádpalota",
            "Csenger",
            "Csepreg",
            "Csongrád",
            "Csorna",
            "Csorvás",
            "Csurgó",
            "Dabas",
            "Debrecen",
            "Demecser",
            "Derecske",
            "Dévaványa",
            "Devecser",
            "Diósd",
            "Dombóvár",
            "Dombrád",
            "Dorog",
            "Dunaföldvár",
            "Dunaharaszti",
            "Dunakeszi",
            "Dunaújváros",
            "Dunavarsány",
            "Dunavecse",
            "Edelény",
            "Eger",
            "Elek",
            "Emőd",
            "Encs",
            "Enying",
            "Ercsi",
            "Érd",
            "Esztergom",
            "Fegyvernek",
            "Fehérgyarmat",
            "Felsőzsolca",
            "Fertőd",
            "Fertőszentmiklós",
            "Fonyód",
            "Fót",
            "Füzesabony",
            "Füzesgyarmat",
            "Gárdony",
            "Göd",
            "Gödöllő",
            "Gönc",
            "Gyál",
            "Gyomaendrőd",
            "Gyömrő",
            "Gyöngyös",
            "Gyöngyöspata",
            "Gyönk",
            "Győr",
            "Gyula",
            "Hajdúböszörmény",
            "Hajdúdorog",
            "Hajdúhadház",
            "Hajdúnánás",
            "Hajdúsámson",
            "Hajdúszoboszló",
            "Hajós",
            "Halásztelek",
            "Harkány",
            "Hatvan",
            "Herend",
            "Heves",
            "Hévíz",
            "Hódmezővásárhely",
            "Ibrány",
            "Igal",
            "Isaszeg",
            "Izsák",
            "Jánoshalma",
            "Jánosháza",
            "Jánossomorja",
            "Jászapáti",
            "Jászárokszállás",
            "Jászberény",
            "Jászfényszaru",
            "Jászkisér",
            "Kaba",
            "Kadarkút",
            "Kalocsa",
            "Kaposvár",
            "Kapuvár",
            "Karcag",
            "Kazincbarcika",
            "Kecel",
            "Kecskemét",
            "Kemecse",
            "Kenderes",
            "Kerekegyháza",
            "Kerepes",
            "Keszthely",
            "Kisbér",
            "Kisköre",
            "Kiskőrös",
            "Kiskunfélegyháza",
            "Kiskunhalas",
            "Kiskunmajsa",
            "Kistarcsa",
            "Kistelek",
            "Kisújszállás",
            "Kisvárda",
            "Komádi",
            "Komárom",
            "Komló",
            "Kondoros",
            "Kozármisleny",
            "Körmend",
            "Körösladány",
            "Kőszeg",
            "Kunhegyes",
            "Kunszentmárton",
            "Kunszentmiklós",
            "Lábatlan",
            "Lajosmizse",
            "Lébény",
            "Lengyeltóti",
            "Lenti",
            "Létavértes",
            "Letenye",
            "Lőrinci",
            "Maglód",
            "Mágocs",
            "Makó",
            "Mándok",
            "Marcali",
            "Máriapócs",
            "Martfű",
            "Martonvásár",
            "Mátészalka",
            "Medgyesegyháza",
            "Mélykút",
            "Mezőberény",
            "Mezőcsát",
            "Mezőhegyes",
            "Mezőkeresztes",
            "Mezőkovácsháza",
            "Mezőkövesd",
            "Mezőtúr",
            "Mindszent",
            "Miskolc",
            "Mohács",
            "Monor",
            "Mór",
            "Mórahalom",
            "Mosonmagyaróvár",
            "Nádudvar",
            "Nagyatád",
            "Nagybajom",
            "Nagyecsed",
            "Nagyhalász",
            "Nagykálló",
            "Nagykanizsa",
            "Nagykáta",
            "Nagykőrös",
            "Nagymányok",
            "Nagymaros",
            "Nyékládháza",
            "Nyergesújfalu",
            "Nyíradony",
            "Nyírbátor",
            "Nyíregyháza",
            "Nyírlugos",
            "Nyírmada",
            "Nyírtelek",
            "Ócsa",
            "Onga",
            "Orosháza",
            "Oroszlány",
            "Ózd",
            "Őrbottyán",
            "Őriszentpéter",
            "Örkény",
            "Pacsa",
            "Paks",
            "Pálháza",
            "Pannonhalma",
            "Pápa",
            "Pásztó",
            "Pécel",
            "Pécs",
            "Pécsvárad",
            "Pétervására",
            "Pilis",
            "Piliscsaba",
            "Pilisvörösvár",
            "Polgár",
            "Polgárdi",
            "Pomáz",
            "Pusztaszabolcs",
            "Putnok",
            "Püspökladány",
            "Rácalmás",
            "Ráckeve",
            "Rakamaz",
            "Rákóczifalva",
            "Répcelak",
            "Rétság",
            "Rudabánya",
            "Sajóbábony",
            "Sajószentpéter",
            "Salgótarján",
            "Sándorfalva",
            "Sárbogárd",
            "Sarkad",
            "Sárospatak",
            "Sárvár",
            "Sásd",
            "Sátoraljaújhely",
            "Sellye",
            "Siklós",
            "Simontornya",
            "Siófok",
            "Solt",
            "Soltvadkert",
            "Sopron",
            "Sülysáp",
            "Sümeg",
            "Szabadszállás",
            "Szarvas",
            "Százhalombatta",
            "Szécsény",
            "Szeged",
            "Szeghalom",
            "Székesfehérvár",
            "Szekszárd",
            "Szendrő",
            "Szentendre",
            "Szentes",
            "Szentgotthárd",
            "Szentlőrinc",
            "Szerencs",
            "Szigethalom",
            "Szigetszentmiklós",
            "Szigetvár",
            "Szikszó",
            "Szob",
            "Szolnok",
            "Szombathely",
            "Tab",
            "Tamási",
            "Tápiószele",
            "Tapolca",
            "Tát",
            "Tata",
            "Tatabánya",
            "Téglás",
            "Tét",
            "Tiszacsege",
            "Tiszaföldvár",
            "Tiszafüred",
            "Tiszakécske",
            "Tiszalök",
            "Tiszaújváros",
            "Tiszavasvári",
            "Tokaj",
            "Tolna",
            "Tompa",
            "Tótkomlós",
            "Tököl",
            "Törökbálint",
            "Törökszentmiklós",
            "Tura",
            "Túrkeve",
            "Újfehértó",
            "Újhartyán",
            "Újkígyós",
            "Újszász",
            "Üllő",
            "Vác",
            "Vaja",
            "Vámospércs",
            "Várpalota",
            "Vásárosnamény",
            "Vasvár",
            "Vecsés",
            "Velence",
            "Vép",
            "Veresegyház",
            "Verpelét",
            "Veszprém",
            "Vésztő",
            "Villány",
            "Visegrád",
            "Záhony",
            "Zalaegerszeg",
            "Zalakaros",
            "Zalalövő",
            "Zalaszentgrót",
            "Zamárdi",
            "Zirc",
            "Zsámbék"
        ]
    }
};

global.Base64 = {

    // private property
    _keyStr: "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=",

    // public method for encoding
    encode: function (input) {
        var output = "";
        var chr1, chr2, chr3, enc1, enc2, enc3, enc4;
        var i = 0;

        input = Base64._utf8_encode(input);

        while (i < input.length) {

            chr1 = input.charCodeAt(i++);
            chr2 = input.charCodeAt(i++);
            chr3 = input.charCodeAt(i++);

            enc1 = chr1 >> 2;
            enc2 = ((chr1 & 3) << 4) | (chr2 >> 4);
            enc3 = ((chr2 & 15) << 2) | (chr3 >> 6);
            enc4 = chr3 & 63;

            if (isNaN(chr2)) {
                enc3 = enc4 = 64;
            } else if (isNaN(chr3)) {
                enc4 = 64;
            }

            output = output +
                this._keyStr.charAt(enc1) + this._keyStr.charAt(enc2) +
                this._keyStr.charAt(enc3) + this._keyStr.charAt(enc4);
        }
        return output;
    },

    // public method for decoding
    decode: function (input) {
        var output = "";
        var chr1, chr2, chr3;
        var enc1, enc2, enc3, enc4;
        var i = 0;

        input = input.replace(/[^A-Za-z0-9\+\/\=]/g, "");

        while (i < input.length) {

            enc1 = this._keyStr.indexOf(input.charAt(i++));
            enc2 = this._keyStr.indexOf(input.charAt(i++));
            enc3 = this._keyStr.indexOf(input.charAt(i++));
            enc4 = this._keyStr.indexOf(input.charAt(i++));

            chr1 = (enc1 << 2) | (enc2 >> 4);
            chr2 = ((enc2 & 15) << 4) | (enc3 >> 2);
            chr3 = ((enc3 & 3) << 6) | enc4;

            output = output + String.fromCharCode(chr1);

            if (enc3 != 64) {
                output = output + String.fromCharCode(chr2);
            }
            if (enc4 != 64) {
                output = output + String.fromCharCode(chr3);
            }
        }

        output = Base64._utf8_decode(output);

        return output;
    },

    // private method for UTF-8 encoding
    _utf8_encode: function (string) {
        string = string.replace(/\r\n/g, "\n");
        var utftext = "";

        for (var n = 0; n < string.length; n++) {

            var c = string.charCodeAt(n);

            if (c < 128) {
                utftext += String.fromCharCode(c);
            }
            else if ((c > 127) && (c < 2048)) {
                utftext += String.fromCharCode((c >> 6) | 192);
                utftext += String.fromCharCode((c & 63) | 128);
            }
            else {
                utftext += String.fromCharCode((c >> 12) | 224);
                utftext += String.fromCharCode(((c >> 6) & 63) | 128);
                utftext += String.fromCharCode((c & 63) | 128);
            }
        }
        return utftext;
    },

    // private method for UTF-8 decoding
    _utf8_decode: function (utftext) {
        var string = "";
        var i = 0;
        var c = c1 = c2 = 0;

        while (i < utftext.length) {

            c = utftext.charCodeAt(i);

            if (c < 128) {
                string += String.fromCharCode(c);
                i++;
            }
            else if ((c > 191) && (c < 224)) {
                c2 = utftext.charCodeAt(i + 1);
                string += String.fromCharCode(((c & 31) << 6) | (c2 & 63));
                i += 2;
            }
            else {
                c2 = utftext.charCodeAt(i + 1);
                c3 = utftext.charCodeAt(i + 2);
                string += String.fromCharCode(((c & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
                i += 3;
            }
        }
        return string;
    }
}