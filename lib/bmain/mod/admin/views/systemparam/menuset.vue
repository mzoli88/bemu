<template>
  <Panel title="Menü beállítások" border h padding>
    <div
      v-for="(menu, menu_key) in menu"
      :key="menu_key"
      class="vflex gap padding"
    >
      <div class="cflex gap">
        <Icon>{{ menu.icon }}</Icon>
        {{ menu.name }}
      </div>
      <Field
        type="radiogroup"
        :startValue="menu.type"
        @change="change"
        :name="'type_' + menu_key"
        :boxes="[
          { value: 1, name: 'Menüpontok az almenü listában látszódnak' },
          { value: 2, name: 'Menüpontok a főmenüben látszódnak' },
        ]"
        :noEdit="!hasPerm('systemparam_edit','SysAdmin')"
      />
      <Field
        v-if="menu_key != 'admin'"
        type="number"
        label="Rendezés"
        min="10"
        max="800"
        :startValue="menu.sort"
        @change="change"
        :name="'sort_' + menu_key"
        :noEdit="!hasPerm('systemparam_edit','SysAdmin')"
      />
    </div>
  </Panel>
</template>

<script>
export default {
  data: function () {
    getStore("menudata").load((s, d) => {
      this.menu = d;
      this.$nextTick(() => (this.mounted = true));
    });
    return {
      menu: null,
      mounted: null,
    };
  },
  methods: {
    change: function () {
      if (!hasPerm("systemparam_edit",'SysAdmin')) return;
      if (!this.mounted) return;
      getStore("menudata").create(this.formGet());
    },
  },
};
</script>