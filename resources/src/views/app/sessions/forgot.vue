<template>
  <div class="auth-layout-wrap" v-if="logo">
    <div class="auth-content">
      <div class="card o-hidden">
        <div class="row">
          <div class="col-md-12">
            <div class="p-4">
              <div class="auth-logo text-center mb-30">
                 <img :src="logo" alt="logo">
              </div>
              <h1 class="mb-3 text-18">{{$t('Forgot_Password')}}</h1>

              <validation-observer ref="Reset_password" v-if="!otp_step">
                <b-form @submit.prevent="Submit_Reset">
                  <validation-provider
                    name="Email or Phone"
                    :rules="{ required: true}"
                    v-slot="validationContext"
                  >
                    <b-form-group :label="$t('Email_or_Phone')" class="text-12">
                      <b-form-input
                        :state="getValidationState(validationContext)"
                        aria-describedby="Identifier-feedback"
                        class="form-control-rounded"
                        type="text"
                        v-model="identifier"
                      ></b-form-input>
                      <b-form-invalid-feedback id="Identifier-feedback">{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                    </b-form-group>
                  </validation-provider>

                  <button
                    type="submit"
                    :disabled="loading"
                    class="btn btn-primary btn-block btn-rounded mt-3"
                  >{{$t('Reset_Password')}}</button>
                  <div v-once class="typo__p" v-if="loading">
                    <div class="spinner sm spinner-primary mt-3"></div>
                  </div>
                </b-form>
              </validation-observer>

              <validation-observer ref="Verify_otp" v-else>
                <b-form @submit.prevent="Submit_Verify">
                  <b-form-group :label="$t('OTP_Code')" class="text-12">
                    <b-form-input
                      class="form-control-rounded"
                      type="text"
                      v-model="otp"
                      required
                      placeholder="Enter 6-digit OTP"
                    ></b-form-input>
                  </b-form-group>

                  <validation-provider
                    name="password"
                    :rules="{ required: true}"
                    v-slot="validationContext"
                  >
                    <b-form-group :label="$t('password')" class="text-12">
                      <b-form-input
                        :state="getValidationState(validationContext)"
                        aria-describedby="password-feedback"
                        class="form-control-rounded"
                        type="password"
                        v-model="password"
                      ></b-form-input>
                      <b-form-invalid-feedback
                        id="password-feedback"
                      >{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                    </b-form-group>
                  </validation-provider>

                  <validation-provider
                    name="confirmation"
                    rules="confirmed:password|required:true"
                    v-slot="validationContext"
                  >
                    <b-form-group :label="$t('Confirm_password')" class="text-12">
                      <b-form-input
                        :state="getValidationState(validationContext)"
                        aria-describedby="confirmation-feedback"
                        class="form-control-rounded"
                        type="password"
                        v-model="password_confirmation"
                      ></b-form-input>
                      <b-form-invalid-feedback
                        id="confirmation-feedback"
                      >{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                    </b-form-group>
                  </validation-provider>

                  <button
                    type="submit"
                    :disabled="loading"
                    class="btn btn-primary btn-block btn-rounded mt-3"
                  >{{$t('submit')}}</button>
                  <div v-once class="typo__p" v-if="loading">
                    <div class="spinner sm spinner-primary mt-3"></div>
                  </div>

                  <div class="mt-3 text-center">
                    <a href="#" @click.prevent="Resend_OTP" class="text-muted">
                      <u>{{$t('Resend_OTP')}}</u>
                    </a>
                  </div>

                  <div class="mt-2 text-center">
                    <a href="#" @click.prevent="otp_step = false" class="text-muted">
                      <u>{{$t('Back')}}</u>
                    </a>
                  </div>
                </b-form>
              </validation-observer>

              <div class="mt-3 text-center">
                <a href="/login"  class="text-muted">
                  <u>{{$t('SignIn')}}</u>
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
import NProgress from "nprogress";

export default {
  metaInfo: {
    // if no subcomponents specify a metaInfo.title, this title will be used
    title: "Forgot Password"
  },
  data() {
    return {
      identifier: "",
      otp: "",
      otp_step: false,
      password: null,
      password_confirmation: null,
      loading: false,
      logo: null,
    };
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
    //------------- Submit Reset Password (step 1: request OTP)
    Submit_Reset() {
      this.$refs.Reset_password.validate().then(success => {
        if (!success) {
          this.makeToast(
            "danger",
            this.$t("Please_fill_the_Email_Adress"),
            this.$t("Failed")
          );
        } else {
          this.Reset_Password();
        }
      });
    },

    //------------- Submit OTP + new password (step 2)
    Submit_Verify() {
      this.$refs.Verify_otp.validate().then(success => {
        if (!success) {
          this.makeToast(
            "danger",
            this.$t("Please_fill_the_form_correctly"),
            this.$t("Failed")
          );
        } else {
          this.Verify_Otp();
        }
      });
    },

    getValidationState({ dirty, validated, valid = null }) {
      return dirty || validated ? valid : null;
    },

    //------ Toast
    makeToast(variant, msg, title) {
      this.$root.$bvToast.toast(msg, {
        title: title,
        variant: variant,
        solid: true
      });
    },

    Reset_Password() {
      // Start the progress bar.
      NProgress.start();
      NProgress.set(0.1);
      this.loading = true;
      axios
        .post("/api/password/send-otp", {
          identifier: this.identifier
        })
        .then(result => {
          if (result.data.status) {
            this.otp_step = true;
            this.makeToast(
              "success",
              result.data.message || this.$t("OTP_sent"),
              this.$t("Success")
            );
          } else {
            this.makeToast(
              "danger",
              result.data.message || this.$t("We_cant_find_a_user_with_that_email_addres"),
              this.$t("Failed")
            );
          }
          NProgress.done();
          this.loading = false;
        })
        .catch(error => {
          this.makeToast(
            "danger",
            this.$t("Failed_to_authenticate_on_SMTP_server"),
            this.$t("Failed")
          );
          NProgress.done();
          this.loading = false;
        });
    },

    Resend_OTP() {
      NProgress.start();
      axios
        .post("/api/password/send-otp", {
          identifier: this.identifier
        })
        .then(result => {
          this.makeToast(
            "success",
            result.data.message || this.$t("OTP_sent"),
            this.$t("Success")
          );
          NProgress.done();
        })
        .catch(error => {
          this.makeToast(
            "danger",
            this.$t("Failed_to_send_OTP"),
            this.$t("Failed")
          );
          NProgress.done();
        });
    },

    Verify_Otp() {
      NProgress.start();
      this.loading = true;
      axios
        .post("/api/password/verify-otp", {
          identifier: this.identifier,
          otp: this.otp,
          password: this.password,
          password_confirmation: this.password_confirmation
        })
        .then(response => {
          if (response.data.code === 1) {
            this.makeToast(
              "success",
              this.$t("Your_Password_has_been_changed"),
              this.$t("Success")
            );
            window.location = '/login';
          } else if (response.data.code === 2) {
            this.makeToast(
              "danger",
              response.data.message || this.$t("We_cant_find_a_user_with_that_email_addres"),
              this.$t("Failed")
            );
          } else if (response.data.code === 3) {
            this.makeToast(
              "danger",
              response.data.message || this.$t("This_password_reset_token_is_invalid"),
              this.$t("Failed")
            );
          }
          NProgress.done();
          this.loading = false;
        })
        .catch(error => {
          this.makeToast("danger", this.$t("InvalidData"), this.$t("Failed"));
          NProgress.done();
          this.loading = false;
        });
    }
  }
};
</script>
