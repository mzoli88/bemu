<template>
  <div
    class="fieldbody checkboxfield"
    :class="{ vflex: !center, cflex: center, checked: value == 'I',unchecked: value != 'I' }"
  >
    <span class="noselect blankch">&nbsp;</span>
    <div
      class="chbox fieldBorder pointer cflex"
      :class="{ required }"
      :tabindex="disabled ? null : 0"
      @keypress.space="onClick"
      @click="onClick"
    >
      <Icon v-if="value == 'I'" :class="{notdefaultcheckedIcon:checkedIcon}">{{checkedIcon || 'f00c'}}</Icon>
      <Icon v-if="value != 'I' && unCheckedIcon" :class="{unCheckedIcon:unCheckedIcon}">{{unCheckedIcon}}</Icon>
    </div>
  </div>
</template>

<script>
export default {
  mixins: [fieldMixin],
  props: {
    center: Boolean,
    checkedIcon: String,
    unCheckedIcon: String,
  },
  watch:{
    value:function(val){
      if(val !== 'I' && val !== 'N'){
        this.value = val ? 'I' : 'N';
      }
    },
  },
  methods: {
    onClick: function (e) {
      e.stopPropagation(); // szülő click esemény ne fusson le. PL grid chbox és smallDetail
      if (this.disabled) return false;
      this.value = this.value == 'I' ? 'N' : 'I';
    },
  },
};
</script>

<style>
.chbox {
  position: relative;
  padding: 0 !important;
  height: 18px;
  width: 18px;
  line-height: initial;
  flex: 0 0 auto;
}
.chboxtext {
  margin-left: 5px;
}
.blankch {
  width: 0px;
}
.chbox .icon{
  color: var(--higlight-text-color);
}
.notdefaultcheckedIcon{
  font-size: 15px;
}
</style>