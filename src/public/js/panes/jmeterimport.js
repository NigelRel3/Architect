var jmeterimport = Vue.component('jmeterimport', {
    data: function() {
        return {
            fileConfig: { types: ".csv,.xml" }
        }
    },
    
    props: ['filechooser', 'importData', 'config', 'tabID'],

    created: function () {
        this.importData.set('importType', 'JMeterImport');
    },
    
    template: `
    <div>
        JMeter Import
        <component :is="filechooser" 
            :key="filechooser.name"
            :config="fileConfig"
            :importData="importData"
            :tabID="tabID">
        </component>
        
        <form>
            <div class="form-group row">
                <div class="col-sm-2">
                        <button @click="$emit('processData')">Process</button>
                </div>
            </div>
        </form>
    </div>
    `
})
