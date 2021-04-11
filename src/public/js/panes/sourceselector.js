var sourceselector = Vue.component('sourceselector', {
    data: function() {
        return {
            groupsSet: false
        }
    },
    
    props: ['config', 'workspace'],

    created: function()  {
        for ( const id in this.workspace.dataSources )  {
            if ( this.workspace.dataSources[id].Group ) {
                this.groupsSet = true;
                break;
            }
        }
    },
    
    template: `
    <div class="w-100">
        Select data sources:
        <table class="table table-striped table-min">
            <thead class="thead-light">
                <tr class="table-compact">
                    <th class="text-center">Active</th>
                    <th>Name</th>
                    <th>Range</th>
                    <th v-if="groupsSet">Group</th>
                    <th>Created</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(source, id) in workspace.dataSources" class="table-compact">
                    <td class="text-center">
                        <input type="checkbox" 
                            :data-id="id"
                            v-model="config.ComponentData.dataSources[id].selected"
                            name="source-id"
                            @change="$emit('updateSourceSelect', id, 
                                        config.ComponentData.dataSources[id].selected)">
                    </td>
                    <td>{{ source.Name }}
                        <img src="/ui/icons/key.svg" alt="" title="key load"
                            v-if="workspace.dataSources[id].GroupKey == 1" />
                    </td>
                    <td>{{ source.DataStartPoint.date 
                                | formatDateTimeRange(source.DataEndPoint.date) }}</td>
                    <td v-if="groupsSet">{{ source.Group }}</td>
                    <td>{{ source.CreatedOn.date | formatDateTime }}</td>
                    <td>{{ source.Notes | truncate(30) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
    `
})
