<template>
  <div class="fieldbody fit hflex">
    <div v-if="!disabled" class="toolbar HtmlToolbar">
      <template v-if="!showCode">
        <select
          class="fieldBorder FontField pointer"
          @change="onFontTypeChange"
        >
          <option selected>Betűkészlet</option>
          <option class="heading">Arial</option>
          <option class="heading">Arial Black</option>
          <option class="heading">Courier New</option>
          <option class="heading">Tahoma</option>
          <option class="heading">Times New Roman</option>
        </select>
        <select
          class="fieldBorder FontSizeField pointer"
          @change="onFontSizeChange"
        >
          <option selected>Betűméret</option>
          <option class="heading">1</option>
          <option class="heading">2</option>
          <option class="heading">3</option>
          <option class="heading">4</option>
          <option class="heading">5</option>
          <option class="heading">6</option>
          <option class="heading">7</option>
          <option class="heading">8</option>
          <option class="heading">9</option>
          <option class="heading">10</option>
          <option class="heading">11</option>
          <option class="heading">12</option>
        </select>
        <div class="cflex htmltoolbarSeparator">|</div>
        <div
          class="Button noselect pointer htmleditorBtn htmleditorColorPicker pictoBtn htmleditorColorPickerText"
          title="Betűszín"
        >
          <span class="icon">&#xf303;</span>
          <Colorpicker @select="onColorSelect" />
        </div>
        <div
          class="Button noselect pointer htmleditorBtn htmleditorColorPicker htmleditorColorPickerbg pictoBtn"
          title="Háttérszín kiválasztása"
        >
          <span class="icon">&#xf591;</span>
          <Colorpicker @select="onBColorSelect" />
        </div>
        <div class="cflex htmltoolbarSeparator">|</div>
        <div
          class="Button noselect pointer htmleditorBtn pictoBtn"
          @click="doFormat('undo')"
          title="Visszavonás"
        >
          <span class="icon">&#xf0e2;</span>
        </div>
        <div
          class="Button noselect pointer htmleditorBtn pictoBtn"
          @click="doFormat('redo')"
          title="Mégis"
        >
          <span class="icon">&#xf01e;</span>
        </div>
        <div
          class="Button noselect pointer htmleditorBtn pictoBtn"
          @click="doFormat('removeFormat')"
          title="Formázás megszüntetése"
        >
          <span class="icon">&#xf87d;</span>
        </div>
        <div class="cflex htmltoolbarSeparator">|</div>
        <div
          class="Button noselect pointer htmleditorBtn pictoBtn"
          @click="doFormat('bold')"
          title="Félkövér"
        >
          <span class="icon">&#xf032;</span>
        </div>
        <div
          class="Button noselect pointer htmleditorBtn pictoBtn"
          @click="doFormat('italic')"
          title="Dőlt"
        >
          <span class="icon">&#xf033;</span>
        </div>
        <div
          class="Button noselect pointer htmleditorBtn pictoBtn"
          @click="doFormat('underline')"
          title="Aláhúzott"
        >
          <span class="icon">&#xf0cd;</span>
        </div>
        <div class="cflex htmltoolbarSeparator">|</div>
        <div
          class="Button noselect pointer htmleditorBtn pictoBtn"
          @click="doFormat('justifyleft')"
          title="Balra igazítás"
        >
          <span class="icon">&#xf036;</span>
        </div>
        <div
          class="Button noselect pointer htmleditorBtn pictoBtn"
          @click="doFormat('justifycenter')"
          title="Középre igazítás"
        >
          <span class="icon">&#xf037;</span>
        </div>
        <div
          class="Button noselect pointer htmleditorBtn pictoBtn"
          @click="doFormat('justifyright')"
          title="Jobbra igazítás"
        >
          <span class="icon">&#xf038;</span>
        </div>
        <div
          class="Button noselect pointer htmleditorBtn pictoBtn"
          @click="doFormat('justifyFull')"
          title="Sorkizárt"
        >
          <span class="icon">&#xf039;</span>
        </div>
        <div class="cflex htmltoolbarSeparator">|</div>
        <div
          class="Button noselect pointer htmleditorBtn pictoBtn"
          @click="doFormat('insertunorderedlist')"
          title="Felsorolás lista"
        >
          <span class="icon">&#xf0ca;</span>
        </div>
        <div
          class="Button noselect pointer htmleditorBtn pictoBtn"
          @click="doFormat('insertorderedlist')"
          title="Számozott lista"
        >
          <span class="icon">&#xf0cb;</span>
        </div>
        <div class="cflex htmltoolbarSeparator">|</div>
        <div
          class="Button noselect pointer htmleditorBtn pictoBtn"
          @click="doFormat('indent')"
          title="Behúzás növelése"
        >
          <span class="icon">&#xf03c;</span>
        </div>
        <div
          class="Button noselect pointer htmleditorBtn pictoBtn"
          @click="doFormat('outdent')"
          title="Behúzás csökkentése"
        >
          <span class="icon">&#xf03b;</span>
        </div>
        <div class="cflex htmltoolbarSeparator">|</div>
        <div
          class="Button noselect pointer htmleditorBtn pictoBtn"
          @click="doLink"
          title="Hivatkozás"
        >
          <span class="icon">&#xf0c1;</span>
        </div>
        <div class="cflex htmltoolbarSeparator">|</div>
        <div
          class="Button noselect pointer htmleditorBtn pictoBtn"
          @click="onInsertImg"
          title="Kép beillesztése"
          v-if="img"
        >
          <span class="icon">&#xf03e;</span>
        </div>
      </template>

      <div
        class="Button noselect pointer htmleditorBtn pictoBtn"
        @click="toogleCode"
        title="HTML kód megtekintése"
      >
        <span class="icon">&#xf121;</span>
      </div>
      <input
        v-show="false"
        type="file"
        ref="img_input"
        accept="image/png, image/jpeg"
        tabindex="-1"
      />
    </div>
    <textarea
      v-if="showCode"
      type="text"
      class="Textfield Htmleditor fit fieldBorder"
      :class="{ required }"
      v-model="value"
      autocomplete="off"
      :disabled="disabled"
    />
    <div
      v-else
      :class="{ required }"
      :contenteditable="disabled ? 'false' : 'true'"
      class="Textfield Htmleditor fit fieldBorder"
      @input="refreshValue"
      ref="htmlbox"
      @paste="onPaste"
    ></div>
  </div>
</template>

<script>
import Colorpicker from "./ColorBox.vue";
export default {
  mixins: [fieldMixin],
  data: function () {
    return {
      showCode: false,
    };
  },
  props: {
    img: Boolean,
  },
  components: {
    Colorpicker,
  },
  mounted: function () {
    this.$refs.htmlbox.innerHTML = this.value;
  },
  watch: {
    value: function (val) {
      if (this.$refs.htmlbox && val != this.$refs.htmlbox.innerHTML)
        this.$refs.htmlbox.innerHTML = val;
    },
  },
  methods: {
    onPaste: function (e) {
      var clipboardData, pastedData;
      e.stopPropagation();
      e.preventDefault();
      clipboardData = e.clipboardData || window.clipboardData;
      pastedData = clipboardData.getData("Text");
      this.doFormat('insertHTML',pastedData.replace(/\n/g, "<br />"));
    },
    doFormat: function (sCmd, sValue) {
      document.execCommand(sCmd, false, sValue);

      // dd (this.$refs.htmlbox);
      // dd(window.getSelection());
      // dd(window.getSelection().baseNode.parentElement);

      this.refreshValue();
    },
    getSelection: function () {
      if (window && window.getSelection) {
        return window.getSelection();
      } else if (document && document.getSelection) {
        return document.getSelection();
      }
      return null;
    },
    refreshValue: function () {
      if (this.$refs.htmlbox) this.value = this.$refs.htmlbox.innerHTML;
    },
    doLink: function () {
      var sLnk = prompt("Kérem, hazározza meg itt az URL-t!", "http://");
      if (!empty(sLnk) && sLnk != "http://" && sLnk != "https://") {
        this.doFormat("createlink", sLnk);
      }
    },
    toogleCode: function () {
      this.showCode = !this.showCode;
      this.$nextTick(function () {
        if (this.$refs.htmlbox) this.$refs.htmlbox.innerHTML = this.value;
      });
    },
    onFontTypeChange: function (e) {
      this.doFormat("fontname", e.target.value);
      e.target.selectedIndex = 0;
    },
    onFontSizeChange: function (e) {
      this.doFormat("fontsize", e.target.value);
      e.target.selectedIndex = 0;
    },
    onColorSelect: function (val) {
      this.doFormat("forecolor", val);
    },
    onBColorSelect: function (val) {
      this.doFormat("backcolor", val);
    },
    onInsertImg: function (e) {
      e.preventDefault();
      var fileInput = this.$refs.img_input;
      var me = this;
      var readFile = function (e) {
        var file = e.target.files[0];
        if (!file) {
          return;
        }
        var reader = new FileReader();
        reader.onload = function (e) {
          me.doFormat("insertimage", e.target.result);
          fileInput.value = null;
        };
        reader.readAsDataURL(file);
      };
      fileInput.onchange = readFile;
      this.$refs.img_input.click();
    },
  },
};
</script>

<style>
.HtmlToolbar {
  margin-bottom: 4px;
  flex-wrap: wrap;
}
.HtmlToolbar .heading {
  color: #000;
  font-size: 14px;
}
.FontSizeField .heading {
  color: #000;
  text-align: right;
  font-size: 14px;
}
.FontField,
.FontSizeField {
  color: #777;
  font-size: 11px;
}
.htmltoolbarSeparator {
  color: #bbb;
}

.Htmleditor {
  min-height: 500px !important;
  min-width: 1100px !important;
}

.HtmlToolbar {
  min-width: 1100px !important;
}

.htmleditorColorPicker .colorTable {
  position: absolute;
  top: 25px;
  z-index: 100;
  display: none;
}
.htmleditorColorPickerText {
  padding: 0;
}
.htmleditorColorPickerbg {
  background-color: #1d428aaa;
  border-color: transparent;
  padding: 0;
  color: #fff;
}
.htmleditorColorPicker:hover .colorTable {
  display: initial;
}

div.Htmleditor {
  overflow: auto;
}
</style>