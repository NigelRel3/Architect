var loginModal = Vue.component('login-modal', {
    data: function() {
        return {
            userName: '',
            password: '',
            errorMessage: ''
        }
    },
    
    methods: {
        login: function() {
            let request = $.ajax({
                url: "/login",
                headers: { 
                    Accept: "application/json"
                },
                type: "post",
                data: { username: this.userName, password: this.password }
            });

            // Callback handler that will be called on success
            request.done(function(response) {
                $('#loginModal').modal('hide');
                // Pass user data to the application component.
                this.$emit('login-ok', response);
                
            }.bind(this));

            // Callback handler that will be called on failure
            request.fail(function(jqXHR, textStatus, errorThrown) {
                this.errorMessage = errorThrown;
            }.bind(this));
        },
        logout: function() {
            Cookies.remove('JWT')
         }
    },

    template: `<div class="modal fade" id="loginModal" tabindex="-1" 
                    aria-labelledby="LoginModal" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Login</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                          <span aria-hidden="true">&times;</span>
                        </button>
                      </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label for="login-userName" class="col-form-label">Username</label>
                                <input type="text" class="form-control" id="login-userName"
                                    placeholder="Enter Username" required 
                                    v-model="userName" />
                            </div>
                            <div class="form-group">
                                <label for="login-password" class="col-form-label">Password</label>
                                <input type="password" class="form-control" id="login-password"
                                    placeholder="Enter Password" required
                                    v-model="password"  />
                            </div>
                            <span id="loginMessage">{{ errorMessage }}</span>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-primary"
                                 v-on:click="login">Login</button>
                            <button type="button" class="btn btn-secondary" 
                                 data-dismiss="modal">Cancel</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>`
});
