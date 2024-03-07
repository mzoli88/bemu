<template>
  <Tab>
    <Panel title="Naplózás" v-if="isSysAdmin()"> <Naplo /> </Panel>
  </Tab>
</template>

<script>
export default {
  components: {
    Naplo: cLoad("naplo"),
  },
};
</script>