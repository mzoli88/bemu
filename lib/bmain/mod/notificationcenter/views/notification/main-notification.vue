<template>
  <Tab>
    <Panel title="E-mail"> <Email /> </Panel>
    <Panel title="Push"> <Push /> </Panel>
    <Panel title="Rendszerüzenet"> <Notification /> </Panel>
  </Tab>
</template>

<script>
export default {
  components: {
    Email: cLoad("email"),
    Notification: cLoad("notification"),
    Push: cLoad("push")
  },
};
</script>
