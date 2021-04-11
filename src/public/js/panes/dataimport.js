var dataimport = Vue.component('dataimport', {
    data: function() {
        return {
            importTypePanels: [],
            selectedImportType: -1,
            fileChooser: 'filechooser',
            importData: null
        }
    },
    
    props: ['workspace', 'config', 'panels', 'tabID'],
    
    created: function() {
        // Find child panels for import
        for (const [key, panel] of Object.entries(this.panels)) {
            if ( panel.ParentPanelTypeID == this.config.ComponentID ) {
                if ( panel.ParentPanelContext == "ImportType" ) {
                    this.importTypePanels.push(panel);
                }
            }
        };
        this.setSelectedImportType();
        
        this.importData = new FormData();
    }, 
    
    beforeUnmount: function() {
        console.log('beforeUnmount');
        this.$emit('updateMenu', {key: this.config.key, 
            name: this.config.name,
            ComponentData: this.config.ComponentData
        });
    },
    
    mounted() {
        this.$watch('selectedImportType', this.selectedImportChange);
    },
    
    methods: {
        selectedImportChange: function(newVal)  {
            this.config.ComponentData.importType = this.importTypePanels[newVal].id;
            this.$emit('updateMenu', {key: this.config.key, 
                name: this.config.name,
                ComponentData: this.config.ComponentData
            });
        },
        
        setSelectedImportType: function()   {
            if ( ! this.config.ComponentData )    {
                this.config.ComponentData = {
                    importType: -1
                };
            }
            let len = this.importTypePanels.length;
            this.selectedImportType = -1;
            for (let i = 0; i < len; i++) {
                if ( this.importTypePanels[i].id == this.config.ComponentData.importType )    {
                    this.selectedImportType = i;
                    break;
                }
            }
       
        },
        
        processData: function()  {
            this.importData.set('name', this.config.Title);
            this.importData.set('userID', this.workspace.UserID);
            this.importData.set('workspace', this.workspace.id);
            
            let request = $.ajax({
                url: '/upload', // ?XDEBUG_SESSION=1
                data: this.importData,
                type: 'POST',
                contentType: false,
                processData: false,
                headers: {
                    Authorization: 'Bearer ' + Cookies.get('JWT')
                }
            });
            
            request.done(function(response) {
                // Pass user data to the menu
//         console.log('request.done');
               this.$emit('updateMenu', {key: this.config.key, 
                    name: this.config.title,
                    ComponentData: response
                });
                this.config.ComponentData.loadID = response.loadID;        
            }.bind(this));
        },
        
        updateData: function()  {
            this.$emit('updateMenu', {key: this.config.key, 
                name: this.config.title,
                ComponentData: this.config.ComponentData
            });
        }
    },
    
    template: `
    <div class="pane2">
        <form>
            <div class="form-group row">
                <label :for="'dl-loadName-' + tabID" class="col-form-label col-sm-2 col-form-label-sm">
                    Data Import Name
                </label>
                <div class="col-sm-4">
                    <input type="text" 
                        class="form-control form-control-sm" 
                        :id="'dl-loadName-' + tabID"
                        required 
                        v-model="config.title" 
                        @change="updateData" />
                </div>
            </div>
            <div class="form-group row">
                <div class="col-sm-6">
                    <div class="form-group row">
                        <label :for="'dl-notes-' + tabID" class="col-form-label col-sm-2 col-form-label-sm">
                            Notes
                        </label>
                        <textarea rows="6" cols="60" 
                            class="form-control form-control-sm col-sm-10"
                            v-model="config.ComponentData.Notes"
                            @change="updateData" />
                    </div>
               </div>
                <div class="col-sm-6">
                    <div class="form-group row">
                        <label :for="'dl-loadGroup-' + tabID" class="col-form-label col-sm-2 col-form-label-sm">
                            Group
                        </label>
                        <div class="col-sm-2">
                            <input type="text" 
                                name="dl-loadGroup"
                                :id="'dl-loadGroup-' + tabID"
                                v-model="config.ComponentData.Group"
                                maxlength="45" 
                                @change="updateData" />
                        </div>
                    </div>
                    <div class="form-group row">
                        <label :for="'dl-loadGroupKey-' + tabID" class="col-form-label col-sm-2 col-form-label-sm">
                            Group Key
                        </label>
                         <div class="col-sm-1">
                            <input type="checkbox" 
                                name="dl-loadGroupKey"
                                :id="'dl-loadGroupKey-' + tabID"
                                v-model="config.ComponentData.GroupKey" 
                                @change="updateData" />
                        </div>
                   </div>
               </div>
           </div>
            <div class="form-group row">
                <label :for="'dl-loadType-' + tabID" class="col-form-label col-sm-2 col-form-label-sm">
                    Import Type
                </label>
                <div class="col-sm-4">
                       <select :id="'dl-loadType-' + tabID"  
                            class="form-control form-control-sm"
                            v-model="selectedImportType">
                                <option value="-1" disabled selected>Select source type</option>
                                <option v-for="(panel, key) in importTypePanels" v-bind:value="key">
                                    {{ panel.Name }}
                                </option>
                        </select>
                </div>
            </div>
        </form>
        
        <div v-if="selectedImportType != -1">
            <component 
                :is="importTypePanels[selectedImportType].ComponentName" 
                :key="importTypePanels[selectedImportType].ComponentName.name"
                :filechooser="fileChooser"
                :importData="importData"
                :config="config"
                :tabID="tabID"
                @processData="processData">
            </component>
        </div>
    </div>
    `
})


