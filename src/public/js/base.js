const app = new Vue({
    el: '#app',

    data: function() {
        return {
            userLoggedId: false,
            userData: null,
            selectedWorkspace: null,
            
            broadcast: null
        }
    },
    
    mounted: function() {
        // If JWT cookie already set, fetch app data
        if (document.cookie.match(/^(.*;)?\s*JWT\s*=\s*[^;]+(.*)?$/))   {
        	this.loggedIn();
        }
        
        this.broadcast = new BroadcastChannel('Arch');
        this.broadcast.onmessage = this.broadcastRX;
        this.broadcast.postMessage({type: 'new'});
    },
    
    methods: {
        logout: function() {
            this.$refs.loginModule.logout();
            this.userLoggedId = false;
            Cookies.remove('JWT');
        },
        
        loggedIn: function()	{
            // Get application data
            let request = $.ajax({
                url: "/loginData",
                headers: { 
                    Accept: "application/json",
                    Authorization: 'Bearer ' + Cookies.get('JWT')
                },
                type: "get"
            });

            request.done(function(response) {
                this.userLoggedId = true;
                this.userData = response;
                this.selectedWorkspace = response.DefaultWorkspace;
            }.bind(this));
            request.fail(function() {
                this.logout();
            }.bind(this));
        },
        
        broadcastRX: function(ev) { 
            console.log(ev);
            if ( ev.data.type == "new" )   {
                this.broadcast.postMessage({config: 'conf'});
            }
        }
    }
})
