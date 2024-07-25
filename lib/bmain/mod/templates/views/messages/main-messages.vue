<template>
  <Grid
    title="Események"
    store="messages"
    sort="modul_name"
    asc
    entity
    :uRoutes="{
      messageUpdate: {
        title: hasPerm('edit_events') ? 'Módosítás' : 'Megtekintés',
        icon: hasPerm('edit_events') ? 'edit' : 'details',
      },
    }"
  >
    <template #email="rowData">
      <div v-if="rowData.email == 'X'" class="check">
        <Icon title="Kitöltve">rf14a</Icon>
      </div>
      <div v-if="rowData.email == 'I'" class="check">
        <Icon title="Nincs kitöltve">rf0c8</Icon>
      </div>
      <div v-if="rowData.email == 'N'" class="check"></div>
    </template>
    <template #push="rowData">
      <div v-if="rowData.push == 'X'" class="check">
        <Icon title="Kitöltve">rf14a</Icon>
      </div>
      <div v-if="rowData.push == 'I'" class="check">
        <Icon title="Nincs kitöltve">rf0c8</Icon>
      </div>
      <div v-if="rowData.push == 'N'" class="check"></div>
    </template>
    <template #notification="rowData">
      <div v-if="rowData.notification == 'X'" class="check">
        <Icon title="Kitöltve">rf14a</Icon>
      </div>
      <div v-if="rowData.notification == 'I'" class="check">
        <Icon title="Nincs kitöltve">rf0c8</Icon>
      </div>
      <div v-if="rowData.notification == 'N'" class="check"></div>
    </template>

    <template v-slot:messageUpdate="props">
      <MessageUpdate v-bind="props" />
    </template>
  </Grid>
</template>

<script>
export default {
  components: {
    MessageUpdate: cLoad("messages.messageUpdate"),
  },
};
</script>

<style>
.check .icon {
  font-size: 25px;
  padding: 0 20px;
}
</style>