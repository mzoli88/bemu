<template>
    <Tab>
        <Panel title="Felhasználók">
            <Grid
                store="usergoupsperms" 
                sort="name"
                asc
            ></Grid>
        </Panel>
        <Panel title="Jogosultságok">
            <Grid
                store="permissions" 
              
            ></Grid>
        </Panel>
    </Tab>
</template>