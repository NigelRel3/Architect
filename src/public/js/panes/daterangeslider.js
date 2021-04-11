/*
Resources for sliders: 
https://codepen.io/garetmckinley/pen/oLBGWR?css-preprocessor=less
https://refreshless.com/nouislider/slider-read-write/
*/
var daterangeslider = Vue.component('daterangeslider', {
    data: function() {
        return {
            errorMsg: '',
            allDatesStart: Number.MAX_SAFE_INTEGER,
            allDatesEnd: null,
            overallStart: null,
            overallEnd: null,
            overallSlider: null,
            sourcesSelected: [],
            rangeUpdated: 0,
            mountComplete: false,
            processEvents: false,
            
            snapNone: 0,
            snapMinutes: 1,
            snapNearest: 2
        }
    },
    
    props: ['config', 'workspace', 'tabID'],

    created: function() {
        for ( const source in this.config.ComponentData.dataSources) {
            if ( this.config.ComponentData.dataSources[source].selected )    {
                this.sourcesSelected.push(source);
//                console.log(this.tabID + "/" + JSON.stringify(this.config));
//                console.log(this.tabID + "/" + JSON.stringify(this.workspace.dataSources));
                let dataSource = this.workspace.dataSources[source];
                let startDate = Date.parse(dataSource.DataStartPoint.date);
                let endDate = Date.parse(dataSource.DataEndPoint.date);
                this.allDatesStart = Math.min(this.allDatesStart, startDate);
                this.allDatesEnd = Math.max(this.allDatesEnd, endDate);
            }
            this.config.ComponentData.dataSources[source].Offset ??= 0;
            this.config.ComponentData.dataSources[source].Group ??= 0;
            this.config.ComponentData.snapTo ??= 0;
        }
        this.overallStart = this.allDatesStart;
        this.overallEnd = this.allDatesEnd;
    },
    
    mounted: function() {
        this.overallSlider = this.$el.ownerDocument.getElementById('overallrange_' + this.tabID);

        noUiSlider.create(this.overallSlider, {
            start: [this.allDatesStart, this.allDatesEnd - 1],
            connect: true,
            range: {
                'min': this.allDatesStart,
                'max': this.allDatesEnd
            },
            step: 1000
        });
        this.overallSlider.noUiSlider.on('update', function(range) {
            if ( this.processEvents )  {
                this.setOverallValues(range);
            }
        }.bind(this));

        for ( const source in this.sourcesSelected )    {
            let id = this.sourcesSelected[source];
            let slider = this.$el.ownerDocument.getElementById('range_' + id + '_' + this.tabID);
            let dataSource = this.workspace.dataSources[id];
            let startDate = Date.parse(dataSource.DataStartPoint.date)
                + (this.config.ComponentData.dataSources[id].Offset || 0);
            let endDate = Date.parse(dataSource.DataEndPoint.date)
                + (this.config.ComponentData.dataSources[id].Offset || 0);

            noUiSlider.create(slider, {
                start: [startDate, endDate ],
                connect: true,
                range: {
                    'min': this.allDatesStart,
                    'max': this.allDatesEnd
                },
                // Set the width to be fixed
                behaviour: "drag-fixed"
            });
            slider.noUiSlider.on('update', function(id, range, handle) {
                // Only react to 1 handle as they are fixed together
                if ( this.processEvents && handle == 0 )  {
                    this.setSpecificValues(range, id);
                }
            }.bind(this, id));
            slider.noUiSlider.on('change', function(range, handle) {
                if ( this.processEvents && handle == 0 )  {
                    this.setOverallSnap();
                    this.storeDates();
                }
            }.bind(this));
        }
        this.processEvents = true;
   },
   
    
    methods:  {
        setSpecificValues: function (range, id )    {
            
            let dataSource = this.workspace.dataSources[id];
            let diff = range[0] - Date.parse(dataSource.DataStartPoint.date);
            this.config.ComponentData.dataSources[id].Offset = diff;
            // Trigger display update
            this.rangeUpdated++;
        },
        
        setOverallValues: function(range)  {
            this.overallStart = parseInt(range[0]);
            this.overallEnd = parseInt(range[1]);
            if ( this.processEvents )   {
                this.updateRanges();
            }
        },
    
        updateRanges: function()    {
           // Loop through other sliders updating start and end times
            for ( const source in this.sourcesSelected) {
                let id = this.sourcesSelected[source];
                let slider = this.$el.ownerDocument.getElementById('range_' + id + '_' + this.tabID);
                // Disable slider if out of range
                let start = new Date(this.workspace.dataSources[id].DataStartPoint.date).valueOf()
                    + this.config.ComponentData.dataSources[id].Offset;
                let end = new Date(this.workspace.dataSources[id].DataEndPoint.date).valueOf()
                    + this.config.ComponentData.dataSources[id].Offset;
                if ( start > this.overallEnd ||
                        end < this.overallStart )    {
                    slider.setAttribute('disabled', true);    
                }
                else    {
                    slider.removeAttribute('disabled');
                    let range = {
                            'min': this.overallStart,
                            'max': (this.overallEnd <= this.overallStart) 
                                ? this.overallStart + 1 : this.overallEnd
                    };
                    slider.noUiSlider.updateOptions({
                        range: range
                    });
                }
            }         
        },
        
        setOverallSnap: function()  {
            this.processEvents = false;
            let ranges = [];
            let snap = false;
            let step = 1000;
            if ( this.config.ComponentData.snapTo == this.snapMinutes )    {
                step = 60000;
            }
            else if ( this.config.ComponentData.snapTo == this.snapNearest )    {
                for ( const source in this.sourcesSelected) {
                    let id = this.sourcesSelected[source];
                    let start = new Date(this.workspace.dataSources[id].DataStartPoint.date).valueOf()
                        + this.config.ComponentData.dataSources[id].Offset;
                    ranges.push([start, start]);
                }
                ranges.sort();
                snap = true;
            }
            
            this.overallSlider.noUiSlider.updateOptions({
                range: {
                    'min': this.allDatesStart,
                    ...ranges,
                    'max': this.allDatesEnd,
                },
                snap: snap,
                step: step
            });
            
            this.overallSlider.noUiSlider.set([this.overallStart, this.overallEnd]);

            this.processEvents = true;
        },
        
        storeDates: function()  {
            this.$emit('updateDateRange', this.allDatesStart, this.allDatesEnd);
        }
    },
    
    template: `
    <div class="container-fluid architect-pane w-100">
        <div class="form-group row no-margin">
            <div class="col-sm-2 small">Overall</div>
            <div class="col-sm-4 small">{{ allDatesStart | formatDateTime }}</div>
            <div class="col-sm-3 text-right small">{{ allDatesEnd | formatDateTime }}</div>
            <div class="col-sm-1 small"></div>
            <div class="col-sm-2 small">
                <select class="form-control form-control-sm"
                        v-model="config.ComponentData.snapTo"
                        @change="setOverallSnap">
                    <option :value="snapNone">No snap</option>
                    <option :value="snapMinutes">Snap to minutes</option>
                    <option :value="snapNearest">Snap to nearest</option>
                </select>
            </div>
        </div>
        <div class="form-group row no-margin">
            <label for="overallrange" class="col-sm-2 col-form-label-sm"></label>
            <div class="col-sm-7">
                <div :id="'overallrange_' + tabID" class="architect-slider"></div>
            </div>
        </div>
        <div class="form-group row no-margin">&nbsp;</div>
        <div class="form-group row no-margin">
            <div class="col-sm-2 small">Selected range</div>
            <div class="col-sm-4 small">{{ overallStart | formatDateTime }}</div>
            <div class="col-sm-3 text-right small">{{ overallEnd | formatDateTime }}</div>
        </div>
        <template v-for="id in sourcesSelected">
            <div  class="form-group row no-margin">
                <div class="col-sm-2 col-form-label-sm"></div>
                <div class="col-sm-7">
                    <div :id="'range_' + id + '_' + tabID" class="architect-slider"></div>
                </div>
            </div>
            <div :key="rangeUpdated +'_' + id" class="form-group row no-margin">
                <label :for="'range_' + id" class="col-sm-2 small">
                    {{ workspace.dataSources[id].Name }}
                    <img src="/ui/icons/key.svg" alt="" title="key load"
                        v-if="workspace.dataSources[id].GroupKey == 1" />
                </label>
                <div class="col-sm-4 small">
                    {{ workspace.dataSources[id].DataStartPoint.date | 
                        formatDateTimeRange(workspace.dataSources[id].DataEndPoint.date) }}
                </div>
                <div class="col-sm-3 text-right small"
                        v-if="config.ComponentData.dataSources[id].Offset != 0">
                    Offset {{ config.ComponentData.dataSources[id].Offset | dateDiff }}
                </div>
                <div class="col-sm-3 text-center small" v-else />
            </div>
        </template>
   </div>
    `
})
