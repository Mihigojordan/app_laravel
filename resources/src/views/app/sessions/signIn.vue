<template>
  <div class="auth-layout-wrap" v-if="logo">
    <div class="auth-content">
      <div class="card o-hidden">
        <div class="row" >
          <div class="col-md-12">
            <div class="p-4">
              <div class="auth-logo text-center mb-30">
                <img :src="logo" alt="logo">
              </div>
              <h1 class="mb-3 text-18">{{$t('SignIn')}}</h1>
              <validation-observer ref="submit_login" v-if="!otp_step">
                <b-form @submit.prevent="Submit_Login">
                  <validation-provider
                    name="Email or Phone"
                    :rules="{ required: true}"
                    v-slot="validationContext"
                  >
                    <b-form-group :label="$t('Email_or_Phone')" class="text-12">
                      <b-form-input
                        :state="getValidationState(validationContext)"
                        aria-describedby="Email-feedback"
                        class="form-control-rounded"
                        type="text"
                        v-model="email"
                      ></b-form-input>
                      <b-form-invalid-feedback id="Email-feedback">{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                    </b-form-group>
                  </validation-provider>

                  <validation-provider
                    name="Password"
                    :rules="{ required: true}"
                    v-slot="validationContext"
                  >
                    <b-form-group :label="$t('password')" class="text-12">
                      <b-form-input
                        :state="getValidationState(validationContext)"
                        aria-describedby="Password-feedback"
                        class="form-control-rounded"
                        type="password"
                        v-model="password"
                      ></b-form-input>
                      <b-form-invalid-feedback
                        id="Password-feedback"
                      >{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                    </b-form-group>
                  </validation-provider>

                  <b-button
                    type="submit"
                    tag="button"
                    class="btn-rounded btn-block mt-2"
                    variant="primary mt-2"
                    :disabled="loading"
                  >{{$t('SignIn')}}</b-button>
                  <div v-once class="typo__p" v-if="loading">
                    <div class="spinner sm spinner-primary mt-3"></div>
                  </div>
                </b-form>
              </validation-observer>

              <div v-else>
                <b-form @submit.prevent="Verify_OTP">
                   <b-form-group :label="$t('OTP_Code')" class="text-12">
                      <b-form-input
                        class="form-control-rounded"
                        type="text"
                        v-model="otp"
                        required
                        placeholder="Enter 6-digit OTP"
                      ></b-form-input>
                    </b-form-group>

                    <b-button
                      type="submit"
                      tag="button"
                      class="btn-rounded btn-block mt-2"
                      variant="primary mt-2"
                      :disabled="loading"
                    >{{$t('Verify_OTP')}}</b-button>

                    <div class="mt-3 text-center">
                      <a href="#" @click.prevent="Resend_OTP" class="text-muted">
                        <u>{{$t('Resend_OTP')}}</u>
                      </a>
                    </div>

                    <div class="mt-2 text-center">
                      <a href="#" @click.prevent="otp_step = false" class="text-muted">
                        <u>{{$t('Back_to_Login')}}</u>
                      </a>
                    </div>
                </b-form>
              </div>

              <div class="mt-3 text-center">
                <a href="/password/reset"  class="text-muted">
                  <u>{{$t('Forgot_Password')}}</u>
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<script>
import { mapGetters, mapActions } from "vuex";
import NProgress from "nprogress";

export default {
  metaInfo: {
    title: "SignIn"
  },
  data() {
    return {
      email: "",
      password: "",
      otp: "",
      otp_step: false,
      userId: "",
      loading: false,
      logo: null,
    };
  },
  computed: {
    ...mapGetters(["isAuthenticated", "error"])
  },
  mounted() {
    axios.get("/api/get-logo-setting")
      .then(response => {
        this.logo = response.data.logo
          ? `/images/${response.data.logo}`
          : "/images/logo.png"; // fallback
      })
      .catch(() => {
        this.logo = "/images/logo.png";
      });
  },

  methods: {
    //------------- Submit Form login
    Submit_Login() {
      this.$refs.submit_login.validate().then(success => {
        if (!success) {
          this.makeToast(
            "danger",
            this.$t("Please_fill_the_form_correctly"),
            this.$t("Failed")
          );
        } else {
          this.Login();
        }
      });
    },

    getValidationState({ dirty, validated, valid = null }) {
      return dirty || validated ? valid : null;
    },

    Login() {
      let self = this;
      // Start the progress bar.
      NProgress.start();
      NProgress.set(0.1);
      self.loading = true;
      axios
        .post("/login",{
          email: self.email,
          password: self.password
        },
        {
          baseURL: '',
        })
        .then(response => {
           console.log("Login response:", response.data);
           if (response.data.otp_required) {
             console.log("OTP required, switching step");
             if (response.data.otp) {
               console.log("*****************************************");
               console.log("DEBUG OTP CODE:", response.data.otp);
               console.log("*****************************************");
             }
             if (response.data.csrf_token) {
               window.axios.defaults.headers.common['X-CSRF-TOKEN'] = response.data.csrf_token;
               let mt = document.head.querySelector('meta[name="csrf-token"]');
               if (mt) mt.content = response.data.csrf_token;
             }
             self.otp_step = true;
             this.makeToast(
               "success",
               response.data.message || this.$t("OTP_sent_to_your_email"),
               this.$t("Success")
             );
           } else {
             console.log("OTP not required, redirecting");
             this.makeToast(
               "success",
               this.$t("Successfully_Logged_In"),
               this.$t("Success")
             );
             window.location = '/';
           }
            
           NProgress.done();
           this.loading = false;
        })
        .catch(error => {
          NProgress.done();
          this.loading = false;
          let message = (error.response && error.response.data && error.response.data.message) 
                        ? error.response.data.message 
                        : this.$t("Incorrect_Login");
          this.makeToast(
              "danger",
              message,
              this.$t("Failed")
            );
        });
    },

    Verify_OTP() {
      let self = this;
      NProgress.start();
      self.loading = true;
      axios
        .post("/verify-otp", {
          email: self.email,
          otp: self.otp
        })
        .then(response => {
          this.makeToast(
            "success",
            this.$t("Successfully_Logged_In"),
            this.$t("Success")
          );
          window.location = '/';
          NProgress.done();
          this.loading = false;
        })
        .catch(error => {
          NProgress.done();
          this.loading = false;
          let message = (error.response && error.response.data && error.response.data.message)
                        ? error.response.data.message 
                        : this.$t("Invalid_OTP");
          this.makeToast(
            "danger",
            message,
            this.$t("Failed")
          );
        });
    },

    Resend_OTP() {
      let self = this;
      NProgress.start();
      axios
        .post("/resend-otp", {
          email: self.email
        })
        .then(response => {
          if (response.data.otp) {
            console.log("*****************************************");
            console.log("RESENT DEBUG OTP CODE:", response.data.otp);
            console.log("*****************************************");
          }
          this.makeToast(
            "success",
            response.data.message || this.$t("OTP_sent_to_your_email"),
            this.$t("Success")
          );
          NProgress.done();
        })
        .catch(error => {
          NProgress.done();
          this.makeToast(
            "danger",
            this.$t("Failed_to_send_OTP"),
            this.$t("Failed")
          );
        });
    },

    //------ Toast
    makeToast(variant, msg, title) {
      this.$root.$bvToast.toast(msg, {
        title: title,
        variant: variant,
        solid: true
      });
    }
  }
};
</script>
