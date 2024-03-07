<template>
  <div class="Pager noselect titleBorder vflex">
    <slot />
    <div class="cflex toolbar pagingBody">
      <template v-if="!noPage">
        <Button
          icon="pageLeftBig"
          title="Első oldal"
          class="pbtn"
          @click="doPage(1)"
          noHighlight
          noSpin
        />
        <Button
          icon="pageLeft"
          title="Előző oldal"
          class="pbtn"
          @click="prev()"
          noSpin
        />
        <div
          class="cflex nowrap pointer"
          title="Jelenlegi oldal / utolsó oldal"
        >
          {{ convInt(st.page) }} / {{ convInt(st.maxPage || 1) }}
        </div>
        <Button
          icon="pageRight"
          title="Következő oldal"
          class="pbtn"
          @click="next()"
          noSpin
        />
        <Button
          icon="pageRightBig"
          title="Utolsó oldal"
          class="pbtn"
          @click="doPage(st.maxPage)"
          noSpin
        />
      </template>
      <Button
        icon="pageRefresh"
        title="Frissítés"
        class="pbtn"
        @click="doLoad"
      />
      <Field
        v-if="!noPage && !hidecombo"
        class="perPageCombo"
        type="combo"
        title="Oldalankénti rekordok száma"
        @change="perpageChange"
        noBlank
        nosearch
        placeHolder=""
        :startValue="perPage"
        nofindFields
        ref="perPageCombo"
        :boxes="[
          { value: 25, name: 25 },
          { value: 50, name: 50 },
          { value: 100, name: 100 },
        ]"
      />

      <div
        v-if="!hideTotal"
        class="total cflex nowrap pointer"
        title="Összesen"
      >
        {{ convInt(st.total) }} db
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: "Pager",
  props: {
    store: String,
    load: Function,
    hidecombo: Boolean,
    noPage: Boolean,
    hideTotal: Boolean,
  },
  activated: function () {
    this.tabPanelChange();
  },

  data: function () {
    var perPage = localStorage.getItem("PageSize");
    if (!perPage) {
      perPage = 25;
    } else {
      perPage = parseInt(perPage);
    }
    if (!this.store) {
      var grid = this.up("Grid"),
        p = { page: 1 };
      if (grid && grid.$attrs.data) {
        p.total = grid.$attrs.data.length;
      }
      return { st: p, perPage: perPage };
    }
    var st = getStore(this.getSt());
    if (!this.noPage) {
      st.perPage(perPage || st.$displayData.perPage ).page(
        st.$displayData.page || 1
      );
    }
    return {
      st: st.$displayData,
      perPage: perPage,
    };
  },

  methods: {
    doLoad: function () {
      if (this.load) {
        this.load();
      }
    },
    tabPanelChange: function () {
      var perPage = localStorage.getItem("PageSize");
      if (!perPage) {
        perPage = 25;
      } else {
        perPage = parseInt(perPage);
      }
      if (
        this.store &&
        this.$refs.perPageCombo &&
        this.$refs.perPageCombo.getValue() != perPage
      ) {
        getStore(this.getSt()).perPage(perPage);
        this.$refs.perPageCombo.setValue(perPage);
      }
    },
    getSt: function () {
      return this.store.split("|").shift();
    },
    doPage: function (page, perPage) {
      if (!this.store) return false;
      if (!perPage && (page == this.st.page || MajaxManager.loading))
        return false;
      if (page > this.st.maxPage) return false;
      if (page < 1) return false;
      var st = getStore(this.getSt());
      if (perPage) st.perPage(perPage);
      st.page(page);
      this.doLoad();
      return true;
    },
    next: function () {
      return this.doPage(this.st.page + 1);
    },
    prev: function () {
      return this.doPage(this.st.page - 1);
    },
    perpageChange: function (val) {
      if (!val) val = 25;
      localStorage.setItem("PageSize", val);
      if (val != this.st.perPage) return this.doPage(1, val);
    },
    convInt: function (number) {
      return number.toString().replace(/(\d)(?=(\d\d\d)+(?!\d))/g, "$1 ");
    },
  },
};
</script>

<style>
.Pager {
  font-weight: bold;
  padding: 0 4px;
  position: relative;
}

.Pager > .pagingBody > .Button {
  min-width: 20px;
}

.Button.pbtn {
  background-color: transparent;
  font-size: 16px;
  border-color: transparent;
  /* color: initial; */
  padding: 0;
}

.perPageCombo .fieldbody {
  width: 72px !important;
}
</style>