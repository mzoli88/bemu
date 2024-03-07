<template>
  <div
    v-if="iconcls || iconString"
    :class="[iconcls, spin ? 'spin' : null, r ? 'regular' : null]"
    class="icon noselect"
    v-html="iconString"
    @click="$emit('click', $event)"
    :title="title"
  />
</template>

<script>
export default {
  emits: ["click"],
  data: function name() {
    return {
      iconString: null,
      iconcls: null,
      r: false,
    };
  },
  watch: {
    icon: function (val) {
      this.setIcon(val);
    },
  },
  methods: {
    setIcon: function (icon) {
      if (/^r/.test(icon)) {
        this.r = true;
        icon = icon.replace("r", "");
      }

      if (!empty(icon) && icon.length < 3) {
        this.iconString = icon;
      } else if (/^[0-9A-Fa-f]{3}$/i.test(icon)) {
        this.iconString = "&#xf" + icon + ";";
      } else if (/^[0-9A-Fa-f]{4}$/i.test(icon)) {
        this.iconString = "&#x" + icon + ";";
      } else if (icon.includes("&#x")) {
        this.iconString = icon + ";";
      } else {
        switch (icon || this.title) {
          case "Bezárás":
            this.iconcls = "close";
            break;
          case "Mégsem":
            this.iconcls = "not";
            break;
          case "Törlés":
            this.iconcls = "delete";
            break;
          case "Mentés":
            this.iconcls = "save";
            break;
          case "Módosítás":
            this.iconcls = "edit";
            break;
          case "Hozzáadás":
            this.iconcls = "add";
            break;
          case "Felvitel":
            this.iconcls = "add";
            break;
          case "Keresés":
            this.iconcls = "search";
            break;
          case "Kiürítés":
            this.iconcls = "clear";
            break;
          case "Megtekintés":
          case "Részletek":
          case "Részletek megtekintése":
            this.iconcls = "view";
            break;
          case "Részletek":
            this.iconcls = "details";
            break;
          case "Publikálás":
            this.iconcls = "publish";
            break;
          case "Export":
          case "Exportálás":
            this.iconcls = "export";
            break;
          case "Import":
          case "Importálás":
            this.iconcls = "import";
            break;
          case "Archiválás":
            this.iconcls = "archive";
            break;
          case "Karbantartás":
            this.iconcls = "karbantart";
            break;
          case "Nyomtatás":
            this.iconcls = "print";
            break;
          case "Letöltés":
            this.iconcls = "download";
            break;
          default:
            this.iconcls = this.icon;
        }
      }
    },
  },
  mounted: function () {
    var icon = this.icon;
    if (!icon && this.$slots.default)
      icon = this.$slots.default()[0].children.trim();
    if (!icon) icon = "";
    this.setIcon(icon);
  },

  props: {
    icon: String,
    title: String,
    spin: Boolean,
  },
};
</script>

<style>
.icon {
  display: inline-block;
}

.icon {
  font-family: "Font Awesome 6 Free";
  font-weight: 900;
  font-size: 18px;
}
.regular.icon {
  font-weight: 400;
}

.icon.close:before {
  content: "\f00d";
}

.icon.not:before {
  content: "\f05e";
}

.icon.delete:before {
  content: "\f1f8";
}

.icon.save:before {
  content: "\f0c7";
}

.icon.edit:before {
  content: "\f303";
}

.icon.add:before {
  content: "\f055";
}

.icon.search:before {
  content: "\f002";
}

.icon.clear:before {
  content: "\f12d";
}

.icon.view:before {
  content: "\f06e";
}

.icon.details:before {
  content: "\f06E";
}

.icon.publish:before {
  content: "\f0a1";
}

.icon.export:before {
  content: "\f1c3";
}

.icon.import:before {
  content: "\f093";
}

.icon.karbantart:before {
  content: "\f0ad";
}

.icon.print:before {
  content: "\f02f";
}

.icon.archive:before {
  content: "\f187";
}

.icon.pageLeftBig:before {
  content: "\f100";
}

.icon.pageLeft:before {
  content: "\f104";
}

.icon.pageRight:before {
  content: "\f105";
}

.icon.pageRightBig:before {
  content: "\f101";
}

.icon.pageRefresh:before {
  content: "\f0E2";
}

.icon.sort.asc::after {
  content: "\f30c";
}

.icon.sort.desc::after {
  content: "\f309";
}

.icon.datepickicon:before {
  content: "\f073";
}

.icon.intervalOn:before {
  content: "\f204";
}

.icon.intervalOff:before {
  content: "\f205";
  color: var(--higlight-text-color);
}

.icon.sDetailDown:before {
  content: "\f078";
}

.icon.sDetailRight:before {
  content: "\f054";
}

.icon.comboBox:before {
  content: "\f107";
}

.icon.upload:before {
  content: "\f093";
}

.icon.download:before {
  content: "\f019";
}

.icon.process:before {
  content: "\f013";
}

.icon.numberfieldAdd:before {
  content: "\f067";
}

.icon.numberfieldSub:before {
  content: "\f068";
}

.icon.checked:before {
  content: "\f00c";
  color: var(--higlight-text-color);
}

.left .collapsedIcon:before,
.collapsed .right .collapsedIcon:before {
  content: "\f137";
}

.right .collapsedIcon:before,
.collapsed .left .collapsedIcon:before {
  content: "\f138";
}

.up .collapsedIcon:before,
.collapsed .down .collapsedIcon:before {
  content: "\f139";
}

.down .collapsedIcon:before,
.collapsed .up .collapsedIcon:before {
  content: "\f13a";
}

/* MEnü ikon */
.Menu .left .collapsedIcon:before {
  content: "\f03b";
}

.Menu.collapsed .left .collapsedIcon:before {
  content: "\f03c";
}
</style>