<template>
  <div class="Numberfield fit fieldbody vflex">
    <input
      type="text"
      class="fieldBorder fit"
      :class="{ required }"
      v-model="value2"
      autocomplete="off"
      :disabled="disabled"
      @blur="onBlur"
    />
    <div
      class="cflex pointer fieldButton noselect"
      :tabindex="disabled ? null : 0"
      @click="sub"
      @keydown.space="sub"
      title="Csökkentés"
      @blur="onBlur"
    >
      <div class="icon numberfieldSub"></div>
    </div>
    <div
      :tabindex="disabled ? null : 0"
      @keydown.space="add"
      @click="add"
      class="fieldButton cflex pointer noselect"
      title="Növelés"
      @blur="onBlur"
    >
      <div class="icon numberfieldAdd"></div>
    </div>
  </div>
</template>

<script>
export default {
  mixins: [fieldMixin],
  emits: ["blur"],
  props: {
    min: [Number, String],
    max: [Number, String],
    negative: Boolean,
    float: [Number, String],
  },
  watch: {
    value: function (val) {
      if (isString(val) && !empty(val)) {
        this.value = this.intConv(val);
      }
      this.value2 = val;
    },
    value2: function (val) {
      if (empty(val)) val = null;
      if (isString(val) && !empty(val)) {
        this.value2 = this.intConv(val);
      }
      if (this.max && this.intConv(val) > this.intConv(this.max)) {
        this.value2 = this.max;
        return;
      }

      this.value = val;
    },
  },
  data: function () {
    return {
      value2: null, // ez azért kell, hogy ha szerkesztjük a mezőt akkor ne legye string a value
    };
  },
  methods: {
    $convertValue: function (val) {
      if (empty(val)) return null;
      if (isNumber(val)) val = val + "";
      if (!isString(val) || empty(val)) {
        return 0;
      }
      if (this.float) {
        val = parseFloat(this.intConv(val));
      } else {
        val = parseInt(this.intConv(val));
      }
      if (!empty(val)) return val;
      return null;
    },
    intConv: function (val) {
      var isnegative = false;
      if (this.negative && val.charAt(0) == "-") {
        isnegative = true;
      } else {
        isnegative = false;
      }

      if (this.float) {
        var tmp = val.split(/[\.\,]/),
          first = tmp.shift().replace(/\D/g, ""),
          second = tmp.join("").replace(/\D/g, "");
        if (second.length > this.float) {
          second = second.substring(0, this.float);
        }

        if (/[\.\,]$/.test(val)) {
          val = (isnegative ? "-" : "") + first + "." || 0;
        } else {
          if (!(val == "-" && this.negative)) {
            if (isnegative && first == 0) {
              val = "-0." + second;
            } else {
              val = parseInt((isnegative ? "-" : "") + first) || 0;
              if (!empty(second)) val += "." + second;
            }
          }
        }
      } else {
        if (!(this.negative && val == "-" )) {
          if(isString(val)) val = val.replace(/\D/g, "");
          val = parseInt((isnegative ? "-" : "") + val) || 0;
        }
      }
      return val;
    },
    add: function () {
      if (this.disabled) return false;
      if (empty(this.value)) this.value2 = this.min || 0;
      this.value++;
    },
    sub: function () {
      if (this.disabled) return false;
      if (empty(this.value) || (!this.negative && this.value < 1))
        this.value2 = this.min || 1;
      if (!this.negative && this.min && this.value - 1 < this.min) {
        return;
      }
      this.value--;
    },
    onBlur: function (e) {
      if (this.value == "-") this.value = null;
      if (this.float && !empty(this.value)) this.value = parseFloat(this.value);
      this.$emit("blur", e);
      if (!this.min) return false;
      if (empty(this.value)) return false;
      if (this.min && this.value < this.min) {
        this.value2 = this.min;
      }
    },
  },
};
</script>