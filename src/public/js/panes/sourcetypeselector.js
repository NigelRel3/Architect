var sourcetypeselector = Vue.component('sourcetypeselector', {
    data: function() {
        return {
            displayTypesForSource: {},
            typesSelected: {},
            baseURL: null,
            dataTypes: {}
        }
    },
    
    props: ['config', 'workspace', 'tabID'],
    
    created: function() {
        this.baseURL = window.location.origin;
        for ( const id in this.workspace.dataSources )   {
            this.displayTypesForSource[id] = false;
            if ( this.config.ComponentData.dataSources[id].dataTypesSelected === undefined )   {
                this.config.ComponentData.dataSources[id].dataTypesSelected = {};
            }
        }
        this.typesSelected = this.config.ComponentData.dataSources;
        
        this.setTypes();
    },

    methods:    {
        toggleTypeSelection: function (id, status)  {
            this.displayTypesForSource[parseInt(id)] = status;
            $('#display-types-open-' + id + "_" + this.tabID).toggle();
            $("#display-types-close-" + id + "_" + this.tabID).toggle();
            $("#table-types-" + id + "_" + this.tabID).toggle();
        },
        
        updateTypes: function(id, subID) {
            if ( id )   {
                this.typesSelected[id].dataTypesSelected[subID] = 
                    !this.typesSelected[id].dataTypesSelected[subID];
            }
            this.$emit('updateSourceSelect', null, this.typesSelected);
        },
        
        setTypes: function()    {
            for ( const typeID in this.typesSelected )  {
                const sourceType = this.workspace.dataSources[typeID].ImportType;
                // Access types for this import type
                this.dataTypes[typeID] = this.workspace.dataTypes[sourceType]['sub'];
            }
        }
    },
    
    template: `
    <div class="w-100">
        Select data source types:
        <table class="table table-striped table-min">
            <thead class="thead-light">
                <tr class="table-compact">
                    <th class="text-center">Types</th>
                    <th>Name</th>
                    <th>Created</th>
                    <th>Start</th>
                    <th>End</th>
                </tr>
            </thead>
            <tbody>
                <template  v-for="(source, id) in workspace.dataSources"
                        v-if="typesSelected[id].selected">
                    <tr class="table-compact">
                        <td class="text-center">
                            <img :src="baseURL + '/ui/icons/arrow-bar-down.svg'"
                                :id="'display-types-open-' + id + '_' + tabID" 
                                @click="toggleTypeSelection(id, true)"/>
                            <img :src="baseURL + '/ui/icons/arrow-bar-up.svg'"
                                style="display:none;"
                                :id="'display-types-close-' + id + '_' + tabID"
                                @click="toggleTypeSelection(id, false)" />
                        </td>
                        <td>{{ source.Name }}</td>
                        <td>{{ source.CreatedOn.date | formatDateTime }}</td>
                        <td>{{ source.DataStartPoint.date | formatDateTime }}</td>
                        <td>{{ source.DataEndPoint.date | formatDateTime }}</td>
                    </tr>
                    <tr :id="'table-types-' + id + '_' + tabID"
                            style="display:none;"
                            class="table-compact">
                        <td colspan="5">
                            <div class="btn-group-toggle" data-toggle="buttons">
                                <template v-for="typeID in dataTypes[id]">
                                    <label :class="(typeID.sub ?'btn-group-header ' : '')
                                                + 'btn btn-light btn-sm' + 
                                                ((typesSelected[id].dataTypesSelected[typeID.id] === true) 
                                                     ? ' active' : '')">
                                        <input type="checkbox" autocomplete="off"
                                            v-model="typesSelected[id].dataTypesSelected[typeID.id]"
                                            @change="updateTypes()" />
                                        {{typeID.Name}}
                                        <img :src="baseURL + '/ui/icons/arrow-right-short.svg'"
                                            :id="'display-types-expand-' + id + '_' + typeID.id"
                                            v-if="typeID.sub && !typesSelected[id].dataTypesSelected[typeID.id]" />
                                        <img :src="baseURL + '/ui/icons/arrow-left-short.svg'"
                                            :id="'display-types-expand-' + id + '_' + typeID.id"
                                            v-if="typeID.sub && typesSelected[id].dataTypesSelected[typeID.id]" />
                                    </label>
                                    <span class="btn-group-toggle" data-toggle="buttons"
                                            v-if="typeID.sub && typesSelected[id].dataTypesSelected[typeID.id]">
                                        <label v-for="typeIDSub in typeID.sub" 
                                                :class="(typesSelected[id].dataTypesSelected[typeIDSub.id] === true) 
                                                         ? 'btn btn-light btn-sm active' 
                                                         : 'btn btn-light btn-sm'">
                                            <input type="checkbox" autocomplete="off"
                                                v-model="typesSelected[id].dataTypesSelected[typeIDSub.id]"
                                                @change="updateTypes(id, typeIDSub.id)"/>
                                            {{typeIDSub.Name}}
                                        </label>
                                    </span>
                                </template>
                            </div>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
    `
})
