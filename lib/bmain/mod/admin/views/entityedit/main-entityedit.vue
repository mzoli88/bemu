<template>
  <Grid store="entities" title="Entitás kezelés">
    <template #details>
      <Grid
        store="users"
        title="Felhasználók"
        parent="entity_id"
        :uRoutes="{
          add: {
            title: 'Entitáshoz adás',
            icon: 'f0c8',
            if: (r) => !r.in_entity,
            click: toogle,
          },
          del: {
            title: 'Eltávolítás',
            icon: 'f14a',
            if: (r) => r.in_entity,
            click: toogle,
          },
        }"
      />
    </template>
  </Grid>
</template>

<script>
export default {
  methods: {
    toogle: function (e, cmp, r) {
      if (!getJog("entity_admin") && !isSysAdmin()) return;
      getStore("users").update(r.id);
      getStore("users").load();
    },
  },
};
</script>

<style scoped>
.add.Button {
  background-color: #aaa;
}
</style>