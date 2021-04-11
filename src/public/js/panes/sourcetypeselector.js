var sourcetypeselector = Vue.component('sourcetypeselector', {
    data: function() {
        return {
            displayTypesForSource: {},
            typesSelected: {},
            baseURL: null
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
    },

    methods:    {
        toggleTypeSelection: function (id, status)  {
            this.displayTypesForSource[parseInt(id)] = status;
            $('#display-types-open-' + id + "_" + this.tabID).toggle();
            $("#display-types-close-" + id + "_" + this.tabID).toggle();
            $("#table-types-" + id + "_" + this.tabID).toggle();
        },
        
        updateTypes: function() {
            this.$emit('updateSourceSelect', null, this.typesSelected);
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
                            <div class="btn-group-toggle d-flex" data-toggle="buttons">
                                <label v-for="typeID in workspace.dataSources[id].typesAvailable" 
                                        :class="(typesSelected[id].dataTypesSelected[typeID] === true) ? 'btn btn-light btn-sm w-100 active' : 'btn btn-light btn-sm w-100'">
                                    <input type="checkbox" autocomplete="off"
                                        v-model="typesSelected[id].dataTypesSelected[typeID]"
                                        @change="updateTypes" />{{workspace.dataTypes[typeID].Name}}
                                </label>
                            </div>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
    `
})
