<template>
    <div class="toolbar" v-if="Queque.run">
        <span></span>

        <Button icon="f013" spin v-if="!Queque.download">
            {{ Queque.name }} folyamatban
            <span style="margin-left:5px" v-if="Queque.signal">{{ Queque.signal }}</span>
        </Button>

        <Button v-if="!Queque.download && Queque.can_cancel" class="stopExportBtn" icon="Mégsem" @click="doStop"
            :title="Queque.name + ' leállítása'"></Button>
        <Button v-if="Queque.download" icon="f019" class="downloadReadyButton Alert" @click="doDownloadExport">
            {{ Queque.download }}
        </Button>
    </div>
</template>

<script>
export default {
    data: function () {
        return {
            Queque: Queque,
        };
    },
    methods: {
        doStop: function () {
            if(SWorker) SWorker.QuequeOff();
            getStore('admin.quedownload').list({
                stopQueque: Queque.stop,
            });
        },
        doDownloadExport: function () {
            getStore('admin.quedownload').download({
                modul_azon: getActiveModul(),
            });
            if(SWorker) SWorker.QuequeOff();
            Queque.run = false;
            Queque.download = false;
            Queque.name = '';
        }
    }
}
</script>

<style>
.stopExportBtn {
    background-color: #ff7979;
    color: #000;
    border-color: #ff7979;
}
</style>