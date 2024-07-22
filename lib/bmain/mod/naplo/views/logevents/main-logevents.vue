<template>
  <Panel title="Log event-ek kigyűjtése" border>
    <Form store="generate" save :afterSave="afterSave">
      <Field label="Modul" type="combo" store="mods" name="modul_azon" @change="onchange"></Field>
      <br>
    </Form>
  </Panel>
</template>

<script>
export default {
  data: function () {
    return { val: null }
  },
  methods: {
    onchange: function (val) {
      this.val = val;
    },
    afterSave: function () {
      this.formSet({
        modul_azon: this.val
      });
    }
  }
}
</script>