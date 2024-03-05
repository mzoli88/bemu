<template>
    <Panel title="Módosítás" border>
        <Form store="users" load :back="back" :save="onSave" :record_id="record_id">
            <Field name="login" label="Felhasználónév" type="display" required />
            <Field name="name" label="Név" type="string" required />
            <Field name="email" label="E-mail" type="string" validation="email|required" />
            <Field v-if="isSysAdmin()" name="sys_admin" label="Supervisor" type="rigennem" startValue="N" />
            <Field name="state_name" label="Jelenlegi státusz" type="display" required />
            <Field name="state_id" label="Új státusz" :store="'userstates|user_id=' + record_id" type="combo" required @change="stateChange" />
            <Field v-if="canJustification" name="inactivation_justification" label="Indoklás" type="textarea" required />
        </Form>
    </Panel>
</template>

<script>
export default {
    props: {
        record_id: [Number, String],
        back: [Function]
    },
    data: function () {
        var me = this;
        getStore('users').onView(function (s, d) {
            me.state_id = d.state_id;
        }, me);
        return {
            state_id: null,
            canJustification: false
        };
    },
    methods: {
        stateChange: function (value, _self) {
            if (this.state_id != value) {
                this.canJustification = true;
            } else {
                this.canJustification = false;
            }
        },
        onSave: function () {
            var me = this;
            if (!this.formValidate()) return;
            var formData = this.formGet();

            if (me.state_id != formData.state_id) {
                msg(
                    "Kérem erősítse meg, hogy biztosan módosítja a felhasználó státuszát?",
                    "i/n",
                    function (s) {
                        if (!s) return;
                        getStore('users').update(me.record_id, formData,
                            function (s, data, code) {
                                if (s) {
                                    me.back();
                                }
                                if (code === 422) {
                                    me.formSetErrors(data);
                                }
                            }
                        );
                    });
            } else {
                getStore('users').update(me.record_id, formData,
                    function (s, data, code) {
                        if (s) {
                            me.back();
                        }
                        if (code === 422) {
                            me.formSetErrors(data);
                        }
                    }
                );
            }
        }
    }
}
</script>