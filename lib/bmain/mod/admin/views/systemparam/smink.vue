<template>
  <Panel border>
    <Form store="admin.params" load :save="hasPerm('systemparam_edit','SysAdmin')">
      <Field
        type="color"
        label="Fejlécek háttérszíne"
        name="title-background-color"
        @change="change"
        :noEdit="!hasPerm('systemparam_edit','SysAdmin')"
      />
      <Field
        type="color"
        label="Fejlécek betűszíne"
        name="title-color"
        @change="change"
        :noEdit="!hasPerm('systemparam_edit','SysAdmin')"
      />
      <Field
        type="color"
        label="Kiemelt betűk színe"
        name="higlight-text-color"
        @change="change"
        :noEdit="!hasPerm('systemparam_edit','SysAdmin')"
      />
      <Field
        type="color"
        label="Kiemelt gombok háttérszíne"
        name="button-higlight-color"
        @change="change"
        :noEdit="!hasPerm('systemparam_edit','SysAdmin')"
      />
      <Field
        type="number"
        min="0"
        max="10"
        startValue="3"
        label="Általános betűméret"
        name="main-size"
        @change="change"
        :noEdit="!hasPerm('systemparam_edit','SysAdmin')"
      />
      <Field
        type="number"
        min="0"
        max="10"
        startValue="3"
        label="Gombok betűmérete"
        name="button-size"
        @change="change"
        :noEdit="!hasPerm('systemparam_edit','SysAdmin')"
      />
      <Field
        type="combo"
        label="Betűtípus"
        name="main-family"
        @change="change"
        :boxes="familyBox"
        :noEdit="!hasPerm('systemparam_edit','SysAdmin')"
      />
    </Form>
    <div class="fit hflex gap padding" v-if="hasPerm('systemparam_edit','SysAdmin')">
      <div>Alapértelmezett stílusok</div>
      <div class="toolbar">
        <span></span>
        <Button
          @click="
            formSet({
              'title-background-color': '#1e5999',
              'title-color': '#ffffff',
              'higlight-text-color': '#1e5999',
              'button-higlight-color': '#ff8200',
              'main-size': 3,
              'button-size': 3,
              'main-family': 'Arial, sans-serif',
            })
          "
        >
          TBA
        </Button>
        <Button
          @click="
            formSet({
              'title-background-color': '#c41717',
              'title-color': '#ffffff',
              'higlight-text-color': '#990b0b',
              'button-higlight-color': '#c41717',
              'main-size': 3,
              'button-size': 3,
              'main-family': 'Verdana, sans-serif',
            })
          "
        >
          Piros
        </Button>
        <Button
          @click="
            formSet({
              'title-background-color': '#008585',
              'title-color': '#ffffff',
              'higlight-text-color': '#005755',
              'button-higlight-color': '#029E9F',
              'main-size': 4,
              'button-size': 4,
              'main-family': 'Tahoma, sans-serif',
            })
          "
        >
          Ftalo-zöld
        </Button>
      </div>
    </div>
  </Panel>
</template>

<script>
export default {
  data: function () {
    return {
      familyBox: [
        { value: "Arial, sans-serif", name: "Arial, sans-serif" },
        { value: "Verdana, sans-serif", name: "Verdana, sans-serif" },
        { value: "Tahoma, sans-serif", name: "Tahoma, sans-serif" },
        {
          value: "'Trebuchet MS', sans-serif",
          name: "Trebuchet MS, sans-serif",
        },
        { value: "'Times New Roman', serif", name: "Times New Roman, serif" },
        { value: "Georgia, serif", name: "Georgia, serif" },
        { value: "Garamond, serif", name: "Garamond, serif" },
        { value: "'Courier New', monospace", name: "Courier New, monospace" },
        {
          value: "'Brush Script MT', cursive",
          name: "Brush Script MT, cursive",
        },
      ],
    };
  },
  methods: {
    change: function () {
      if (isLoading()) return;
      this.$root.changeCssVars(this.formGet());
    },
  },
};
</script>