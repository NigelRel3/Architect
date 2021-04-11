var dockerimport = Vue.component('dockerimport', {
    data: function() {
        return {
            containers: null,
            hideInactive: false,
            displayTimeLength: null,
            displayTimeStart: null,
            timeStart: null,
            captureState: false,
            containerRefresh: null,
            baseDisplay: null,
            captureRunning: false,
            captureInterval: 5
        }
    },
    
    props: ['filechooser', 'importData', 'config'],

    created: function () {
        this.importData.set('importType', 'DockerImport');
        this.listContainers();
        if ( ! this.config.ComponentData.containers )    {
            this.config.ComponentData.containers = {};
       }

    },

    mounted: function() {
        this.startContainerTimer();
        // Monitor the tab to stop the update when no longer active
        let element = this.$el.parentElement.parentElement;
        this.baseDisplay = new MutationObserver(function(){
            if ( this.containerRefresh )    {
                clearInterval(this.containerRefresh);
            }
            // Also re-enable timer if making visible again
            if(element.classList.contains('active')){
                this.listContainers();
                this.startContainerTimer();
            }
        }.bind(this));
        this.baseDisplay.observe(element, { attributes: true, childList: false });
    },
    
    beforeUnmount: function() {
        this.tidyUp();
    },
    
    beforeDestroy: function() {
        this.tidyUp();
    },
    
    methods:  {
        tidyUp: function()  {
            // Stop monitoring for return to tab
            this.baseDisplay.disconnect();
            if ( this.containerRefresh )    {
                clearInterval(this.containerRefresh);
            }
            // If in capture still, call to complete the process
            if ( this.captureState )    {
                $.ajax({
                    url: '/docker/stats/complete/' + this.config.ComponentData.loadID,
                    type: 'POST',
                    contentType: false,
                    processData: false,
                    headers: {
                        Authorization: 'Bearer ' + Cookies.get('JWT')
                    }
                });
            }
            this.captureState = false;
        },
        
        startContainerTimer: function()  {
            this.containerRefresh = setInterval(function(){
                this.listContainers();
            }.bind(this), 5000);
         },
        
        listContainers: function()  {
            let request = $.ajax({
                url: "/docker/",
                headers: { 
                Accept: "application/json",
                    "Authorization": "Bearer " + Cookies.get('JWT')
                },
                type: "get"
            });

            request.done(function(response) {
                for ( const id in response ) {
                    if (this.config.ComponentData.containers[id] === undefined )    {
                        this.config.ComponentData.containers[id] = {selected: false};
                    }
                }
                this.containers = response;
            }.bind(this));
            request.fail(function()    {
                // Stop refresh happening if errors
                if ( this.containerRefresh )    {
                    clearInterval(this.containerRefresh);
                }
            }.bind(this));
        },
        
        processCapture: function()  {
            if ( ! this.captureState )    {
                console.log("Start capture");
                // If no loadID defined, process this first
                if ( ! this.config.ComponentData.loadID )   {
                    this.$emit('processData');
                }
                this.captureData();
            }
        },
        
        captureData: function(ids = null) {
            this.captureRunning = true;
            if ( this.timeStart == null )    {
                this.timeStart = new Date();
                this.displayTimeStart = this.timeStart.toLocaleTimeString();
            }
            let timeLength = (new Date() - this.timeStart)/1000;
            let lengthDate = new Date();
            lengthDate.setSeconds(timeLength%60);
            lengthDate.setMinutes(Math.floor(timeLength/60));
            lengthDate.setHours(Math.floor(timeLength/3600));
            this.displayTimeLength = lengthDate.toLocaleTimeString();
            let selectedContainers = [];
            for ( const id in this.containers ) {
                if ( this.config.ComponentData.containers[id].selected ) {
                    selectedContainers.push(this.containers[id].names[0].replace(/\\|\//g,''));
                }
            }
            let url = '/docker/stats/' + this.config.ComponentData.loadID
                        + '/' + this.captureInterval + '/' + JSON.stringify(selectedContainers);
            if ( ids )  {
                url += "/" + JSON.stringify(ids);
            }
            let request = $.ajax({
                    url: url,
                    data: this.importData,
                    type: 'GET',
                    contentType: false,
                    processData: false,
                    headers: {
                        Authorization: 'Bearer ' + Cookies.get('JWT')
                    }
            });
            
            request.done(function(response) {
                if ( this.captureState )    {
                    this.captureData(response.ids);
                }      
                else    {
                    let timeLength = (new Date() - this.timeStart)/1000;
                    let lengthDate = new Date();
                    lengthDate.setSeconds(timeLength%60);
                    lengthDate.setMinutes(Math.floor(timeLength/60));
                    lengthDate.setHours(Math.floor(timeLength/3600));
                    this.displayTimeLength = lengthDate.toLocaleTimeString();
                    this.captureRunning = false;
                    $.ajax({
                            url: '/docker/stats/complete/' + this.config.ComponentData.loadID,
                            type: 'POST',
                            contentType: false,
                            processData: false,
                            headers: {
                                Authorization: 'Bearer ' + Cookies.get('JWT')
                            }
                    });
                }  
            }.bind(this));
            
        },
        
        changeSettings: function ()  {
            this.$emit('updateMenu', {key: this.config.key, 
                name: this.config.name,
                ComponentData: this.config.ComponentData
            });
        }
    },
    
    template: `
    <div class="w-100">
        <div class="form-group flex">
            <label class="col-form-label col-sm-2 col-form-label-sm">
                Docker Containers
            </label>
            <div class="btn-group-toggle float-right" data-toggle="buttons">
                <label class="btn btn-light btn-sm">
                    <input type="checkbox" class="form-control form-control-sm" 
                        checked 
                        autocomplete="off"
                        required 
                        v-model="hideInactive" />
                    {{ hideInactive ? 'Show all containers'
                            : 'Show only running containers' }}
                </label>
            </div>
        </div>
        <table class="table table-striped table-min"
                v-if="containers">
            <thead class="thead-light">
                <tr class="table-compact row">
                    <th class="text-center col-1">Active</th>
                    <th class="col-2">Name</th>
                    <th class="col-2">IP</th>
                    <th class="col-2">State</th>
                    <th class="col-2">Status</th>
                    <th class="col">Image</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(container, id) in containers" 
                        class="table-compact row"
                        v-if="hideInactive == false || container.state == 'running'">
                    <td class="text-center col-1">
                        <input type="checkbox" 
                            v-if="container.state == 'running'"
                            @change="changeSettings"
                            v-model="config.ComponentData.containers[id].selected">
                    </td>
                    <td class="col-2">{{ container.names[0] }}</td>
                    <td class="col-2">{{ container.ip }}</td>
                    <td class="col-2">{{ container.state }}</td>
                    <td class="col-2">{{ container.status }}</td>
                    <td class="col">{{ container.image | truncate(30) }}</td>
                </tr>
            </tbody>
        </table>
        <div class="form-group row"
                v-if="containers">
            <label class="col-form-label col-sm-1 col-form-label-sm">
                Capture
            </label>
            <div class="btn-group-toggle" data-toggle="buttons">
                <label class="btn btn-secondary btn-sm">
                    <input type="checkbox" 
                        class="form-control form-control-sm" 
                        checked 
                        autocomplete="off"
                        @click="processCapture()"
                        required 
                        v-model="captureState" />
                    {{ captureState ? 'Stop' : (captureRunning ? 'Stopping' : 'Start') }}
                </label>
            </div>
            <div v-if="timeStart" class="col-sm">
                <label class="col-form-label col-sm-2 col-form-label-sm">
                    Capture start
                </label>
                <input type="time"
                    class="form-control-sm" 
                    step="1" disabled
                    v-model="displayTimeStart" />
                <label class="col-form-label col-sm-2 col-form-label-sm">
                    lapsed
                </label>
                <input type="time"
                    class="form-control-sm" 
                    step="1" disabled
                    v-model="displayTimeLength" />
            </div>
        </div>
    </div>
    `
})

