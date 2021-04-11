var projectdetails = Vue.component('projectdetails', {
    data: function() {
        return {
            jsonConfig: null
            }
    },
    
    props: ['workspace', 'config', 'panels', 'tabID'],
    
    computed: {
        workspaceName: function() {
            return this.workspace.Name;
        },
        description: function() {
            return this.config.ComponentData.description;
        },
     },
     
     watch: { 
        workspaceName: function(newVal) {
            this.$emit('updateMenu', {
                key: this.config.key, 
                name: newVal
            });
        },
        description: function() {
            this.$emit('updateMenu', {
                key: this.config.key, 
                ComponentData: this.config.ComponentData
            });
        }
    },
    
    template: `
    <div class="pane2">
    	Project Details
        <form>
            <div class="form-group row">
                <label for="pd-workspaceName" class="col-form-label col-sm-2 col-form-label-sm">
                	Workspace Name
                </label>
                <div class="col-sm-6">
                	<input type="text" class="form-control form-control-sm" 
                		:id="'pd-workspaceName_' + tabID"
                    	required 
                        v-model="workspace.Name" />
                </div>
            </div>
            <div class="form-group row">
                <label for="pd-description" class="col-form-label col-sm-2 col-form-label-sm">
                    Description
                </label>
                <div class="col-sm-6">
                    <input type="text" class="form-control form-control-sm" 
                        :id="'pd-description_' + tabID"
                        required 
                        v-model="config.ComponentData.description" />
                </div>
            </div>
            <div class="form-group row">
                <label class="col-form-label col-sm-2 col-form-label-sm">
                    Date created
                </label>
                <div class="col-sm-6 architect-text-small">
                    {{ workspace.CreatedOn.date | formatDate}}
                </div>

            </div>
            <div class="form-group row">
            	<div class="col-sm-8">
    			<textarea rows="6" cols="60" class="form-control form-control-sm col-sm-7">
                    {{ jsonConfig }}
    			</textarea>
    			</div>
            </div>
        </form>
    
    </div>
    `,

})
