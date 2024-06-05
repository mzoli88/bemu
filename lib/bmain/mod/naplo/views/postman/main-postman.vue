<template>
    <Panel title="Fájlrendszer dokumentálás" border>
        <Form store="generate" save :afterSave="afterSave">
            <Field label="Postman collection (json)" type="file" name="file" @change="onchange" />
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