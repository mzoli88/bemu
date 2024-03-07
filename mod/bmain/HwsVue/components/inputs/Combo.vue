<template>
  <div
    class="fieldbody hflex Select fit Combobox"
    :class="{ oponedCombo: showDropdown, closedCombo: !showDropdown }"
  >
    <div class="vflex comboInputBody fit">
      <input
        type="text"
        class="Textfield fit fieldBorder"
        :class="{ pointer: nosearch, required }"
        @click="nosearch ? onClick() : null"
        v-model="searchValue"
        autocomplete="off"
        :placeholder="disabled ? null : placeHolder"
        ref="inputField"
        @blur="onBlur"
        @keydown="keySearch"
        :readonly="disabled || nosearch"
        :disabled="disabled"
      />
      <div class="fieldButton cflex pointer" @click="onClick">
        <div v-if="isLoading" class="vflex fit">
          <Transition name="maskfade2">
            <div class="fit cflex">
              <Icon class="spin">f51f</Icon>
            </div>
          </Transition>
        </div>
        <slot v-else>
          <Icon v-if="comboIcon">{{ comboIcon }}</Icon>
          <span v-else class="icon comboBox"></span>
        </slot>
      </div>
    </div>
    <Dropdown
      class="comboDropdown hflex"
      v-if="showDropdown"
      ref="comboDropdown"
      @blur="onBlur"
      @keydown="keyNavigate"
      noDfocus
    >
      <div v-if="isLoading" class="vflex fit combomaskInside">
        <Transition name="maskfade2">
          <div class="fit cflex">
            <Icon class="spin">f51f</Icon>
          </div>
        </Transition>
      </div>
      <div v-else class="comboDropdownBody fit Body">
        <div
          v-if="!noBlank"
          @click="select(null)"
          class="pointer ComboOption"
          :class="{ focus: focusPointer === -1 }"
        >
          &#60; Üres &#62;
        </div>
        <div
          v-for="(box, index) in dinamicBox"
          :key="index"
          @click="select(box.value, box.name)"
          class="ComboOption"
          :class="tmpRowClass(box, index)"
        >
          <div :class="box.detail ? 'bold' : null">{{ box.name }}</div>
          <div v-if="box.detail">{{ box.detail }}</div>
        </div>
      </div>
      <Pager v-if="store" :store="store" :load="load" hidecombo ref="pager" />
    </Dropdown>
  </div>
</template>

<script>
import Pager from "../Pager.vue";
export default {
  mixins: [fieldMixin],
  emits: ["recordChange"],
  props: {
    store: String,
    nosearch: {
      type: Boolean,
      default: function (p) {
        return !p.store;
      },
    },
    noBlank: Boolean,
    placeHolder: {
      type: String,
      default: "Kérem válasszon!",
    },
    boxes: [Array, Object],
    selected: Array,
    manualSelect: Function,
    rowClass: Function,
    parent: String,
    query: Object,
    comboIcon: String,
    noEdit: Boolean,
  },
  data: function () {
    if (this.parent) {
      var parent = this.up("Form").down({ name: this.parent });
      if (parent){
        if (empty(parent.getValue())) {
          this.up("Field").disable();
        }else{
          this.parent_id = parent.getValue();
        }
      }
    }

    var boxes;

    if (isArray(this.boxes)) {
      boxes = this.boxes;
    }

    if (isObject(this.boxes)) {
      boxes = [];
      for (var i in this.boxes) {
        boxes.push({ value: i, name: this.boxes[i] });
      }
    }

    var me = this,
      displayValue = null;
    if (boxes && this.value) {
      var found = boxes.filter(function (r) {
        return r.value == me.value;
      });
      if (found.length) {
        displayValue = found.pop().name;
      }
    }

    return {
      dinamicBox: boxes || [],
      searchValue: displayValue,
      displayValue: displayValue,
      OldDisplayValue: displayValue,
      showDropdown: false,
      focusPointer: -1,
      isLoading: false,
    };
  },
  watch: {
    displayValue: function (val) {
      this.searchValue = val;
    },
    searchValue: function (val) {
      clearTimeout(this.delayTimer);
      if (val == this.displayValue) return;
      this.close();
      if (this.nosearch || !isString(val)) return;
      var me = this;
      me.dinamicBox = []; // load legyen a következő combo megnyitásnál
      me.delayTimer = setTimeout(function () {
        me.search(val);
      }, 1000);
    },
    value: function (val) {
      //a szülő szól a gyerekeknek, hogy megváltozott az értéke
      var field = this.up("Field"),
        form = field.up("Form"),
        children;

      if (form) {
        children = field.up().findFields({ parent: field.name }, false);
        if (!empty(children)) {
          children.each(function (ch) {
            if (ch.$refs.input.parentChange) ch.$refs.input.parentChange(val);
          });
        }
      }

      this.searchLastValue = null;

      var me = this;
      if (empty(val)) {
        me.displayValue = null;
        return;
      }

      //empty kell mert ha value 0 akkor beragad
      if (!empty(val)) {
        if (this.store) {
          if (!this.noChangeLoad) this.load(val);
          this.noChangeLoad = false;
        } else {
          me.setDisplayByValue(val);
        }
      }
    },
  },
  methods: {
    search: function (val) {
      if (this.nosearch) return;
      clearTimeout(this.delayTimer);

      //ne keressen a háttérben ha már elhagytuk a mezőt (nincs focus)
      if (!this.$el.contains(document.activeElement)) {
        return;
      }

      val = val || this.searchValue;

      if (this.store) {
        if (
          (isString(val) && empty(val)) ||
          (isString(val) && val != this.displayValue)
        ) {
          this.open();
          this.load(null, val);
        }
      }
    },
    getRecord: function (val, box) {
      val = val || this.value;
      box = box || this.dinamicBox;
      return box
        .filter(function (r) {
          return r.value == val;
        })
        .shift();
    },
    setDisplayByValue: function (val, data) {
      var me = this;
      data = data || me.dinamicBox;
      var find = data.filter(function (r) {
        return r.value == val;
      });
      if (find.length) {
        this.displayValue = find[0].name;
        this.OldDisplayValue = find[0].name;
        this.$emit("recordChange", find[0]);
      } else {
        this.displayValue = null;
        this.value = null;
        this.OldDisplayValue = null;
      }
    },
    tmpRowClass: function (row, index) {
      var out = [];
      if (this.rowClass) out = this.rowClass(row, index) || [];
      if (this.focusPointer == index) out.push("focus");

      if (this.selected) {
        var has = this.selected.filter(function (el) {
          return el.value == row.value;
        });
        if (has.length) {
          out.push("mselected");
        } else {
          out.push("pointer");
        }
      } else {
        out.push("pointer");
      }

      return out;
    },

    load: function (record_id, search, fn) {
      if (!this.store) return;

      var me = this,
        tmp = this.store.split("|"),
        stname = tmp.shift(),
        query = { "sort>": "name" },
        st = getStore(stname);

      if (this.query) query = { ...query, ...this.query };

      if (this.$root.debug && record_id) {
        //formfill közben ne csináljon felesleges request-eket
        if (FormFill.run) {
          me.isLoading = false;
          me.noChangeLoad = false;
          me.setDisplayByValue(
            record_id,
            st.$lastResponse ? st.$lastResponse.data : []
          );
          return;
        }
      }
      // dd(stname);
      //kell a nextTick, megvárni, hogy nyitott legyen a combo, különben nem mködik a lapozás
      this.$nextTick(function () {
        me.isLoading = true;
        if (!empty(this.$attrs.parent_id)) {
          this.parent_id = this.$attrs.parent_id;
        }
        if (this.parent_id) {
          query.parent_id = this.parent_id;
        }
        if (!empty(search)) {
          query.name = search;
          this.searchValue = search;
          this.searchLastValue = search;
        } else {
          if (!empty(this.searchLastValue)) query.name = this.searchLastValue;
        }
        if (!empty(record_id)) query.value = record_id;
        if (!empty(tmp)) {
          for (var i in tmp) {
            var tmp2 = tmp[i].split("=");
            query[tmp2[0]] = tmp2[1];
          }
        }
        st.load(
          "hide",
          // empty(record_id) ? "noMaskOn" : null,
          empty(record_id) ? null : "noTotal",
          query,
          function (s, data) {
            me.isLoading = false;
            me.noChangeLoad = false; //ne akadjon be a noChangeLoad
            if (!s) return;
            if (!empty(record_id)) {
              me.setDisplayByValue(record_id, data.data);
            } else {
              me.dinamicBox = data.data || [];
            }
          }
        );
      });
    },
    onClick: function () {
      if (this.noEdit) return;
      if (this.disabled) return;
      if (this.showDropdown) return;
      this.displayValue = null;

      this.searchValue = null;

      this.$nextTick(function () {
        this.open(true);
        this.load();
      });
    },
    open: function (focus) {
      if (this.noEdit) return;
      if (this.disabled) return false;
      this.searchLastValue = null;
      this.showDropdown = true;
      if (focus) {
        this.$nextTick(function () {
          this.$refs.comboDropdown.focus();
        });
      }
    },
    close: function () {
      if (this.store) getStore(this.store).page(1);
      this.searchLastValue = null;
      // if (!this.showDropdown) return;
      this.showDropdown = false;
      this.focusPointer = -1;
    },
    select: function (value, name) {
      if (this.noEdit) return;
      this.noChangeLoad = true;
      if (this.selected) {
        var has = this.selected.filter(function (el) {
          return el.value == value;
        });
        if (has.length) return;
      }
      if (this.manualSelect) {
        this.manualSelect(value, name);
        this.value = null;
        this.displayValue = null;
        this.OldDisplayValue = null;
        this.searchValue = null;
      } else {
        this.value = value;
        this.displayValue = name;
        this.OldDisplayValue = name;
        this.searchValue = name;
        this.$emit("recordChange", this.getRecord());
      }
      this.close();
    },
    onBlur: function (e) {
      if (!this.$el.contains(e.relatedTarget)) {
        this.close();
        if (this.value) {
          this.displayValue = this.OldDisplayValue;
          this.searchValue = this.OldDisplayValue;
        } else {
          this.searchValue = null;
        }
      } else {
        this.$refs.inputField.focus();
      }
    },
    sroll: function () {
      this.$nextTick(function () {
        if (this.$refs.comboDropdown) {
          var active = this.$refs.comboDropdown.$el.querySelector("div.focus");
          if (active) {
            active.scrollIntoView({
              behavior: "auto",
              block: "center",
              inline: "center",
            });
          }
        }
      });
    },
    keySearch: function (e) {
      if (this.disabled) return false;
      if (this.isLoading) {
        e.preventDefault();
        return;
      }
      switch (e.keyCode) {
        case 13: //enter
          // e.preventDefault();
          this.search();
          break;
        case 38: //Up
          // this.displayValue = null;
          // this.searchValue = null;
          e.preventDefault();
          this.open(true);
          if (this.dinamicBox.length == 0) this.load();
          this.focusPointer = this.dinamicBox.length - 1;
          this.sroll();
          break;
        case 40: //Down
          e.preventDefault();
          if (this.searchValue == this.OldDisplayValue) {
            this.displayValue = null;
            this.searchValue = null;
          }
          this.focusPointer = 0;
          this.$nextTick(function () {
            this.open(true);
            if (this.dinamicBox.length == 0) this.load();
            this.sroll();
          });
          break;
      }
    },
    keyNavigate: function (e) {
      if (this.isLoading) {
        e.preventDefault();
        return;
      }
      switch (e.keyCode) {
        case 9: //Tab
          e.preventDefault();
          this.$refs.inputField.focus();
          this.close();
          break;
        // case 13: //enter
        case 32: //space
          e.preventDefault();
          var active = this.$refs.comboDropdown.$el.querySelector("div.focus");
          if (active) {
            active.click();
          }
          this.$refs.inputField.focus();
          break;

        case 40: //Down
          e.preventDefault();
          if (this.focusPointer >= this.dinamicBox.length - 1) {
            this.$refs.inputField.focus();
            break;
          }
          if (this.focusPointer + 1 == this.dinamicBox.length) break;
          this.focusPointer += 1;
          this.sroll();
          break;

        case 38: //Up
          e.preventDefault();
          if (this.focusPointer < 0) {
            this.$refs.inputField.focus();
            break;
          }
          this.focusPointer -= 1;
          this.sroll();
          break;

        case 37: //left
          e.preventDefault();
          if (this.$refs.pager.prev()) {
            this.focusPointer = -1;
            this.sroll();
          }
          break;

        case 39: //Right
          e.preventDefault();
          if (this.$refs.pager.next()) {
            this.focusPointer = -1;
            this.sroll();
          }
          break;
        default:
          if (this.nosearch) {
            // store nélküli keresés
          } else {
            this.$refs.inputField.focus();
          }
      }
    },
    parentChange: function (parent_id) {
      var field = this.up("Field");
      //mumlticombo-val is működik
      if (!empty(parent_id)) {
        field.enable();
        this.parent_id = parent_id;

        //multi combo
        if (this.$parent.valueObj) {
          this.$parent.parent_id = parent_id;
        } else {
          if (this.value) {
            this.noChangeLoad = true;
            this.load(this.value);
          }
        }
      } else {
        field.setValue();
        field.disable();
        if (this.$parent.valueObj) {
          this.$parent.value = [];
          this.$parent.parent_id = false;
        }
      }
    },
  },
  components: {
    Pager,
  },
};
</script>

<style>
.comboDropdown .mselected {
  text-decoration: line-through;
}

.comboDropdown {
  padding: 0 !important;
  background-color: #fff;
  border-radius: 4px;
  overflow: hidden;
}

.comboDropdown .ComboOption {
  font-size: 14px;
  padding: 4px;
  min-height: 18px;
}

.comboDropdown .ComboOption:last-child {
  border-bottom-left-radius: 4px;
  border-bottom-right-radius: 4px;
}

.comboDropdown .ComboOption:first-child {
  border-top-left-radius: 4px;
  border-top-right-radius: 4px;
}

.comboDropdown .Pager {
  border-top: 1px solid #d5d7db;
  color: #444;
}

.comboDropdown .Pager .Button {
  color: #444;
}

.ComboOption:nth-child(even) {
  background: #eee;
}

.comboDropdown:focus .comboDropdownBody .focus,
.comboDropdown .ComboOption:hover {
  background-color: #c6d9fb;
}

.multicomboselectedrow > .Button {
  width: 25px;
  height: 25px;
  flex: 0 0 25px;
  font-size: 14px;
  padding: 0;
  margin: 2px;
}

.combomaskInside {
  min-height: 100px;
  min-width: 100px;
}
.combomaskInside .icon {
  font-size: 40px;
}
</style>