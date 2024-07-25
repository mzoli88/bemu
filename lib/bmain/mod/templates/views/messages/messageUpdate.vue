<template>
    <Panel title="Módosítás" border>
        <Form store="messages" load :back="back" :save="hasPerm('edit_events')" :record_id="record_id">

            <Panel>
                <Field name="modul_name" label="Küldő modul" type="display" />
                <Field name="message_name" label="Esemény" type="display" />
                <Field name="addressee" label="Kinek küldi" type="display" />
                <Field name="variables" label="Sablonban megadható változók" type="display" />
            </Panel>

            <Panel title="E-mail" border v-if="email == 'I'">
                <Field name="email_from" label="Feladó e-mail cím" type="text" hasRowData="email=I" :noEdit="!hasPerm('edit_events')" validation="email" />
                <Field name="email_subj" label="E-mail tárgy" type="text" hasRowData="email=I" :noEdit="!hasPerm('edit_events')" />
                <Field name="email_ct" label="E-mail szöveg" type="html" img hasRowData="email=I" :noEdit="!hasPerm('edit_events')" />
            </Panel>

            <Panel title="Push" border v-if="push == 'I'">
                <Field name="push_subj" label="Push tárgy" type="text" hasRowData="push=I" :noEdit="!hasPerm('edit_events')" />
                <Field name="push_text" label="Push szöveg" type="textarea" hasRowData="push=I" :noEdit="!hasPerm('edit_events')" />
            </Panel>

            <Panel title="Rendszerüzenet" border v-if="notification == 'I'">
                <Field name="notification_text" label="Rendszerüzenet" type="html" img hasRowData="notification=I" :noEdit="!hasPerm('edit_events')" />
            </Panel>
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
        getStore('messages').onView(function (s, d) {
            me.email = d.email;
            me.notification = d.notification;
            me.push = d.push;
        }, me);
        return {
            email: 'N',
            notification: 'N',
            push: 'N',
        };
    }
}
</script>
