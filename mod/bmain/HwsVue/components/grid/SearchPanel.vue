<template>
  <Panel class="GridSearchPanel" title="Keresés" collapsible :collapsed="collapsed" size="360" h>
    <Form @onMounted="setDefault" :fields="fields" @onEnter="search" topToolbar topLabels noFixWidth isSearch>
      <template #toolbar>
        <Button @click="search" title="Keresés" />
        <Button @click="testSearch" icon="f610" title="Keresés teszt" v-if="$root.debug" />
        <Button @click="testSearchOne" icon="f492" title="Keresés teszt egy mezőre" v-if="$root.debug" />
        <Button @click="testSort" icon="f0dc" title="Rendezés teszt" v-if="$root.debug" />
        <Button @click="removeSearch" title="Kiürítés" />
      </template>
    </Form>
  </Panel>
</template>

<script>
// import { toRaw } from "vue";

export default {
  props: {
    store: String,
    fields: Array,
    defaultSearch: Object,
    collapsed: Boolean,
  },
  watch: {
    defaultSearch: function () {
      this.$nextTick(function () {
        this.formReset();
        this.setDefault();
      });
    },
    fields: function () {
      this.$nextTick(() => {
        if (!this.collapsed) this.setDefault();
      });
    }
  },
  updated: function () {
    this.refreshSearchPanel();
  },
  mounted: function () {
    this.$nextTick(() => {
      if (!this.collapsed) this.setDefault();
    });
  },
  methods: {
    refreshSearchPanel: function () {
      var val = this.up("Grid").fastFilterValue;
      if (!val) return;
      this.findFields({
        fastFilter: Array,
      })
        .show()
        .forEach(function (field) {
          if (!field.fastFilter.includes(val)) field.hide();
        });
    },
    search: function () {
      if (this.formValidate()) {
        if (!this.store) return false;
        getStore(this.store).page(1).search(this.formGet(true)).load();
      }
    },
    removeSearch: function () {
      this.formReset();
      this.setDefault();
      this.search();
    },
    setDefault: function () {
      this.refreshSearchPanel();
      if (this.defaultSearch) {
        //klónozzuk, mert az objektumok, tömbök módosítási képesek visszacsatolódni és felülírják a defaultSearch értékét
        // this.formSet(toRaw(this.defaultSearch));
        // this.formSet({ ...this.defaultSearch });
        // sajnos csak ez a klónozás tökéletes, a többinél még előfordultak visszacsatolások
        this.formSet(JSON.parse(JSON.stringify(this.defaultSearch)));
      }
    },
    testSort: function () {
      if (!this.store) return false;
      TestSort.run(getStore(this.store), this);
    },
    testSearch: function () {
      if (!this.store) return false;
      TestSearch.run(getStore(this.store), this);
    },
    testSearchOne: function () {
      if (!this.store) return false;
      TestSearch.run(getStore(this.store), this, true);
    },
  },
  destroyed: function () {
    if (!this.store) return false;
    getStore(this.store).search();
  },
};
</script>