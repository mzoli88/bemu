<template>
    <Panel title="Adatbázis dokumentálás" border>
        <Form store="generate" save :afterSave="afterSave">
            <Field label="Prefix" name="prefix" @change="onchange"></Field>
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
                dirs: this.val
            });
        }
    }
}
</script>