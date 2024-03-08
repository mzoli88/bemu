<template>
  <div class="Datefield fieldbody fit vflex" :class="{ open: datepicker }">
    <input
      type="text"
      class="Textfield fit fieldBorder"
      :class="{ required }"
      v-model="displayValue"
      autocomplete="off"
      :placeholder="'év. hónap. nap.' + (time ? ' óra : perc' : '')"
      ref="mainInpu"
      @keydown.down.up="pickerShow"
      @blur="onBlur"
      :disabled="disabled"
    />
    <Datepicker
      v-if="datepicker"
      :select="pickerSelect"
      :selected="tmpvalue"
      :min="min || minVal"
      :max="max || maxVal"
      ref="picker"
    />
    <TimePicker v-if="time && selectTime" ref="timepicker" />
    <div
      @click="pickerShow"
      :tabindex="disabled ? null : 0"
      @keydown.space="pickerShow"
      class="fieldButton cflex pointer"
    >
      <div class="datepickericontext" v-if="hasSlot()">
        <slot />
      </div>
      <div class="icon datepickicon"></div>
    </div>
  </div>
</template>

<script>
import Datepicker from "./Datepicker.vue";
import TimePicker from "./Timepicker.vue";
export default {
  mixins: [fieldMixin],
  props: {
    min: String,
    max: String,
    time: Boolean,
    from: String,
    to: String,
  },
  data: function () {
    return {
      lastGoodValue: null,
      lastDisplayValue: null,
      displayValue: null,
      tmpvalue: null,
      datepicker: false,
      selectTime: false,
      maxVal: null,
      minVal: null,
    };
  },
  watch: {
    displayValue: function (val, old) {
      // dd(val, old);
      if (!empty(val)) {
        if (this.time) {
          if (
            !/^[0-9]{0,4}\.{0,1}[0-9]{0,2}\.{0,1}[0-9]{0,2}\.{0,1}\s{0,1}[0-9]{0,2}\:{0,1}[0-9]{0,2}$/.test(
              val
            )
          ) {
            this.displayValue = old || "";
            return;
          }
        } else {
          if (
            !/^[0-9]{0,4}\.{0,1}[0-9]{0,2}\.{0,1}[0-9]{0,2}\.{0,1}$/.test(val)
          ) {
            this.displayValue = old || "";
            return;
          }
        }
        val = this.regChange(val, old);
        if (this.valid(val)) {
          this.lastDisplayValue = val;
          this.tmpvalue = this.conv(val);
          this.value = this.tmpvalue;
        }

        this.displayValue = val;
      }
    },
    value: function (val) {
      if (val instanceof Date) {
        this.value = this.format(val);
      }
      if (isString(val)) {
        if (/\./.test(val)) {
          this.value = this.conv(val);
          return;
        }
        var v = this.iconv(val);
        if (this.displayValue != v) {
          this.displayValue = v;
        }

        if (this.to) {
          var cp = this.up("Form").down({
            name: this.to,
          });

          if (cp) cp.$refs.input.minVal = val;
        }
        if (this.from) {
          var cp = this.up("Form").down({
            name: this.from,
          });
          if (cp) cp.$refs.input.maxVal = val;
        }
      } else {
        this.displayValue = val;
      }
    },
  },
  methods: {
    cutTime: function (date) {
      return date.split(" ").shift();
    },
    valid: function (val) {
      if (this.time) {
        if (/^[0-9]{4}\.[0-9]{2}\.[0-9]{2}\.\s[0-9]{2}\:[0-9]{2}$/.test(val)) {
          var error = Validators.datetime(this.conv(val));
          if (!error && this.from) {
            var cp = this.up("Form").down({
              name: this.from,
            });
            if (cp) {
              let val2 = cp.getValue();
              if (val2 && val <= this.iconv(val2)) error = "Hibás intervallum";
            }
          }
          if (!error && this.to) {
            var cp = this.up("Form").down({
              name: this.to,
            });
            if (cp) {
              let val2 = cp.getValue();
              if (val2 && val >= this.iconv(val2)) error = "Hibás intervallum";
            }
          }
          if (error) {
            this.up("Field").errors = [error];
          } else {
            this.up("Field").errors = [];
            return true;
          }
        }
      } else {
        if (/^[0-9]{4}\.[0-9]{2}\.[0-9]{2}\.$/.test(val)) {
          var error = Validators.date(this.conv(val));
          if (!error && this.from) {
            var cp = this.up("Form").down({
              name: this.from,
            });
            if (cp) {
              let val2 = cp.getValue();
              if (val2 && val < this.iconv(val2)) error = "Hibás intervallum";
            }
          }
          if (!error && this.to) {
            var cp = this.up("Form").down({
              name: this.to,
            });
            if (cp) {
              let val2 = cp.getValue();
              if (val2 && val > this.iconv(val2)) error = "Hibás intervallum";
            }
          }
          // dd(this.conv(val));
          if (error) {
            this.up("Field").errors = [error];
          } else {
            this.up("Field").errors = [];
            return true;
          }
        }
      }
      return false;
    },
    conv: function (val) {
      if (this.time) {
        var tmp = val.split(" ");
        return (
          tmp.shift().split(".").join("-").replace(/-$/, "") + " " + tmp.shift()
        );
      } else {
        return val.split(".").join("-").replace(/-$/, "");
      }
    },
    iconv: function (val) {
      if (this.time) {
        var tmp = val.split(" ");
        return tmp.shift().split("-").join(".") + ". " + tmp.shift();
      } else {
        return val.split("-").join(".") + ".";
      }
    },
    regChange: function (val, old) {
      // dd(/^[0-9]{4}$/.test(val), /^[0-9]{3}$/.test(old), val, old);
      if (/^[0-9]{4}$/.test(val) && /^[0-9]{3}$/.test(old)) {
        return val + ".";
      }
      if (
        /^[0-9]{4}\.[0-9]{2}$/.test(val) &&
        /^[0-9]{4}\.[0-9]{1}$/.test(old)
      ) {
        return val + ".";
      }
      if (
        /^[0-9]{4}\.[0-9]{2}\.[0-9]{2}$/.test(val) &&
        /^[0-9]{4}\.[0-9]{2}\.[0-9]{1}$/.test(old)
      ) {
        return val + "." + (this.time ? " " : "");
      }

      if (
        /^[0-9]{4}\.[0-9]{2}\.[0-9]{2}\.\s[0-9]{2}$/.test(val) &&
        /^[0-9]{4}\.[0-9]{2}\.[0-9]{2}\.\s[0-9]{1}$/.test(old)
      ) {
        return val + ":";
      }

      return val;
    },
    pickerShow: function (e) {
      if (this.disabled) return false;
      if (e) e.preventDefault();
      this.datepicker = true;
      this.$nextTick(function () {
        this.$refs.picker.$el.focus();
      });
    },
    pickerSelect: function (value) {
      if (this.time) {
        // dd(value.split(" ").length);
        if (value.split(" ").length == 1) {
          this.datepicker = false;
          this.selectTime = true;
          this.timepickerVAlue = value;
        } else {
          this.selectTime = false;
        }
        // this.tmpVal = value;
      } else {
        this.datepicker = false;
        this.$nextTick(function () {
          this.$refs.mainInpu.focus();
        });
      }
      this.displayValue = value;
    },
    onBlur: function () {
      if (!empty(this.displayValue)) {
        this.displayValue = this.lastDisplayValue;
      } else {
        this.value = null;
      }
    },
    format: function (date) {
      if (this.time) {
        return (
          date.getFullYear() +
          "." +
          ("0" + (date.getMonth() + 1)).slice(-2) +
          "." +
          ("0" + date.getDate()).slice(-2) +
          ". " +
          ("0" + date.getHours()).slice(-2) +
          ":" +
          ("0" + date.getMinutes()).slice(-2)
        );
      }
      return (
        date.getFullYear() +
        "." +
        ("0" + (date.getMonth() + 1)).slice(-2) +
        "." +
        ("0" + date.getDate()).slice(-2) +
        "."
      );
    },
  },
  components: {
    Datepicker,
    TimePicker,
  },
};
</script>