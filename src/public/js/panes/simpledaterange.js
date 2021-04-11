var simpledaterange = Vue.component('simpledaterange', {
    data: function() {
        return {
            errorMsg: ''
        }
    },
    
    props: ['config', 'workspace', 'tabID'],

    template: `
    <div class="container-fluid architect-pane">
        <div class="form-group row">
            <label for="sdr-start" class="col-form-label col-sm-1 col-form-label-sm">
                Start
            </label>
            <div class="col-sm-4">
                <input type="datetime-local" 
                    :id="'sdr-start_' + tabID"
                    name="sdr-start"
                    min="2000-01-01T00:00" 
                    :max="config.ComponentData.endDateTime"
                    v-model="config.ComponentData.startDateTime"
                    step="1"
                    @change="$emit('updateDate', startDate, endDate)">
            </div>
            <label for="sdr-end" class="col-form-label col-sm-1 col-form-label-sm">
                End
            </label>
            <div class="col-sm-4">
                <input type="datetime-local" 
                    :id="'sdr-end_' + tabID"
                    name="sdr-end"
                    :min="config.ComponentData.startDateTime" 
                    max="2060-01-01T00:00"
                    v-model="config.ComponentData.endDateTime"
                    step="1"
                    @change="$emit('updateDate', startDate, endDate)">
            </div>
        </div>
        <div class="form-group row" v-if="errorMsg != ''">
            <p class="text-danger">{{ errorMsg }}</p>
        </div>
   </div>
    `
})
