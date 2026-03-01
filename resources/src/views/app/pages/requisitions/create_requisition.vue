<template>
  <div class="main-content">
    <breadcumb :page="$t('CreateRequisition')" :folder="$t('Procurement')"/>
    <div v-if="isLoading" class="loading_page spinner spinner-primary mr-3"></div>

    <validation-observer ref="create_requisition" v-if="!isLoading">
      <b-form @submit.prevent="Submit_Requisition">
        <b-row>
          <b-col lg="12" md="12" sm="12">
            <b-card>
              <b-row>
                 <!-- date  -->
                <b-col lg="4" md="4" sm="12" class="mb-3">
                  <validation-provider
                    name="date"
                    :rules="{ required: true}"
                    v-slot="validationContext"
                  >
                    <b-form-group :label="$t('date') + ' ' + '*'">
                      <b-form-input
                        :state="getValidationState(validationContext)"
                        aria-describedby="date-feedback"
                        type="date"
                        v-model="requisition.date"
                      ></b-form-input>
                      <b-form-invalid-feedback
                        id="date-feedback"
                      >{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                    </b-form-group>
                  </validation-provider>
                </b-col>

                <!-- warehouse -->
                <b-col lg="4" md="4" sm="12" class="mb-3">
                  <validation-provider name="warehouse" :rules="{ required: true}">
                    <b-form-group slot-scope="{ valid, errors }" :label="$t('warehouse') + ' ' + '*'">
                      <v-select
                        :class="{'is-invalid': !!errors.length}"
                        :state="errors[0] ? false : (valid ? true : null)"
                        @input="Selected_Warehouse"
                        v-model="requisition.warehouse_id"
                        :reduce="label => label.value"
                        :placeholder="$t('Choose_Warehouse')"
                        :options="warehouses.map(warehouses => ({label: warehouses.name, value: warehouses.id}))"
                      />
                      <b-form-invalid-feedback>{{ errors[0] }}</b-form-invalid-feedback>
                    </b-form-group>
                  </validation-provider>
                </b-col>

                <!-- Status  -->
                <b-col lg="4" md="4" sm="12" class="mb-3">
                  <validation-provider name="Status" :rules="{ required: true}">
                    <b-form-group slot-scope="{ valid, errors }" :label="$t('Status') + ' ' + '*'">
                      <v-select
                        :class="{'is-invalid': !!errors.length}"
                        :state="errors[0] ? false : (valid ? true : null)"
                        v-model="requisition.status"
                        :reduce="label => label.value"
                        :placeholder="$t('Choose_Status')"
                        :options="
                            [
                              {label: 'Pending', value: 'pending'},
                              {label: 'Completed', value: 'completed'},
                            ]"
                      ></v-select>
                      <b-form-invalid-feedback>{{ errors[0] }}</b-form-invalid-feedback>
                    </b-form-group>
                  </validation-provider>
                </b-col>

                <!-- Product -->
                <b-col md="12" class="mb-5">
                  <h6>{{$t('ProductName')}}</h6>
                  <div id="autocomplete" class="autocomplete">
                    <input 
                     :placeholder="$t('Scan_Search_Product_by_Code_Name')"
                      @input='e => search_input = e.target.value' 
                      @keyup="search(search_input)"
                      @focus="handleFocus"
                      @blur="handleBlur"
                      ref="product_autocomplete"
                      class="autocomplete-input" />
                    <ul class="autocomplete-result-list" v-show="focused">
                      <li class="autocomplete-result" v-for="product_fil in product_filter" @mousedown="SearchProduct(product_fil)">{{getResultValue(product_fil)}}</li>
                    </ul>
                  </div>
                </b-col>

                <!-- Order products  -->
                <b-col md="12">
                  <h5>{{$t('order_products')}} *</h5>
                  <div class="table-responsive">
                    <table class="table table-hover">
                      <thead class="bg-gray-300">
                        <tr>
                          <th scope="col">#</th>
                          <th scope="col">{{$t('ProductName')}}</th>
                          <th scope="col">{{$t('Current_stock')}}</th>
                          <th scope="col">{{$t('Qty')}}</th>
                          <th scope="col" class="text-center">
                            <i class="fa fa-trash"></i>
                          </th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-if="details.length <=0">
                          <td colspan="5">{{$t('NodataAvailable')}}</td>
                        </tr>
                        <tr v-for="detail in details">
                          <td>{{detail.detail_id}}</td>
                          <td>
                            <span>{{detail.code}}</span>
                            <br>
                            <span class="badge badge-success">{{detail.name}}</span>
                          </td>
                          <td>
                            <span
                              class="badge badge-outline-warning"
                            >{{detail.stock}} {{detail.unit}}</span>
                          </td>
                          <td>
                            <div class="quantity">
                              <b-input-group>
                                <b-input-group-prepend>
                                  <span
                                    class="btn btn-primary btn-sm"
                                    @click="decrement(detail ,detail.detail_id)"
                                  >-</span>
                                </b-input-group-prepend>
                                <input
                                  class="form-control"
                                  @keyup="Verified_Qty(detail,detail.detail_id)"
                                  v-model.number="detail.quantity"
                                >
                                <b-input-group-append>
                                  <span
                                    class="btn btn-primary btn-sm"
                                    @click="increment(detail ,detail.detail_id)"
                                  >+</span>
                                </b-input-group-append>
                              </b-input-group>
                            </div>
                          </td>
                          <td>
                            <i @click="delete_Product_Detail(detail.detail_id)" class="i-Close-Window text-25 text-danger"></i>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </b-col>

                <b-col md="12" class="mt-3">
                  <b-form-group :label="$t('Note')">
                    <textarea
                      v-model="requisition.notes"
                      rows="4"
                      class="form-control"
                      :placeholder="$t('Afewwords')"
                    ></textarea>
                  </b-form-group>
                </b-col>
                <b-col md="12">
                  <b-form-group>
                    <b-button variant="primary" @click="Submit_Requisition" :disabled="SubmitProcessing"><i class="i-Yes me-2 font-weight-bold"></i> {{$t('submit')}}</b-button>
                    <div v-once class="typo__p" v-if="SubmitProcessing">
                      <div class="spinner sm spinner-primary mt-3"></div>
                    </div>
                  </b-form-group>
                </b-col>
              </b-row>
            </b-card>
          </b-col>
        </b-row>
      </b-form>
    </validation-observer>
  </div>
</template>

<script>
import { mapActions, mapGetters } from "vuex";
import NProgress from "nprogress";

export default {
  metaInfo: {
    title: "Create Requisition"
  },
  data() {
    return {
      focused: false,
      timer:null,
      search_input:'',
      product_filter:[],
      isLoading: true,
      SubmitProcessing:false,
      warehouses: [],
      products: [],
      details: [],
      requisition: {
        id: "",
        date: new Date().toISOString().slice(0, 10),
        status: "pending",
        notes: "",
        warehouse_id: "",
      },
    };
  },
  computed: {
    ...mapGetters(["currentUserPermissions","currentUser"])
  },

  mounted() {
    this.Get_Elements();
  },

  methods: {
    Get_Elements() {
      axios
        .get("requisitions/create")
        .then(response => {
          this.warehouses = response.data.warehouses;
          this.isLoading = false;
        })
        .catch(response => {
          this.isLoading = false;
        });
    },

    Selected_Warehouse(value) {
      if (value === null) {
        this.requisition.warehouse_id = "";
      }
      this.search_input = "";
      this.product_filter = [];
      this.Get_Products_By_Warehouse(value);
    },

    Get_Products_By_Warehouse(id) {
      NProgress.start();
      NProgress.set(0.1);
      axios
        .get("get_products_by_warehouse/" + id + "?stock=" + 0 + "&product_service=" + 0)
        .then(response => {
          this.products = response.data;
          NProgress.done();
        })
        .catch(error => {
          NProgress.done();
        });
    },

    Submit_Requisition() {
      this.$refs.create_requisition.validate().then(success => {
        if (!success) {
          this.makeToast(
            "danger",
            this.$t("Please_fill_the_form_correctly"),
            this.$t("Failed")
          );
        } else if (this.details.length <= 0) {
          this.makeToast(
            "warning",
            "Please add at least one product",
            this.$t("Warning")
          );
        } else {
          this.Create_Requisition();
        }
      });
    },

    Create_Requisition() {
      this.SubmitProcessing = true;
      NProgress.start();
      NProgress.set(0.1);
      axios
        .post("requisitions", {
          date: this.requisition.date,
          warehouse_id: this.requisition.warehouse_id,
          status: this.requisition.status,
          notes: this.requisition.notes,
          details: this.details
        })
        .then(response => {
          NProgress.done();
          this.makeToast(
            "success",
            "Requisition Created Successfully",
            this.$t("Success")
          );
          this.SubmitProcessing = false;
          this.$router.push({ name: "index_requisition" });
        })
        .catch(error => {
          NProgress.done();
          this.makeToast("danger", this.$t("InvalidData"), this.$t("Failed"));
          this.SubmitProcessing = false;
        });
    },

    getValidationState({ dirty, validated, valid = null }) {
      return dirty || validated ? valid : null;
    },

    makeToast(variant, msg, title) {
      this.$root.$bvToast.toast(msg, {
        title: title,
        variant: variant,
        solid: true
      });
    },

    handleFocus() { this.focused = true },
    handleBlur() { this.focused = false },

    search() {
      if (this.timer) clearTimeout(this.timer);
      if (this.search_input.length < 2) return this.product_filter = [];
      if (this.requisition.warehouse_id != "") {
        this.timer = setTimeout(() => {
          const product_filter = this.products.filter(product => product.code === this.search_input || product.barcode.includes(this.search_input));
          if (product_filter.length === 1) {
            this.SearchProduct(product_filter[0]);
          } else {
            this.product_filter = this.products.filter(product => {
              return (
                product.name.toLowerCase().includes(this.search_input.toLowerCase()) ||
                product.code.toLowerCase().includes(this.search_input.toLowerCase())
              );
            });
          }
        }, 800);
      } else {
        this.makeToast("warning", this.$t("SelectWarehouse"), this.$t("Warning"));
      }
    },

    getResultValue(result) {
      return result.code + " " + "(" + result.name + ")";
    },

    SearchProduct(result) {
      if (this.details.some(detail => detail.code === result.code)) {
        this.makeToast("warning", this.$t("AlreadyAdd"), this.$t("Warning"));
      } else {
        let detail = {
          detail_id: this.details.length + 1,
          product_id: result.id,
          name: result.name,
          code: result.code,
          stock: result.qte,
          quantity: 1,
          unit: result.unit,
          unit_id: result.unit_id,
          product_variant_id: result.product_variant_id
        };
        this.details.push(detail);
      }
      this.search_input = "";
      this.$refs.product_autocomplete.value = "";
      this.product_filter = [];
    },

    increment(detail, id) {
      detail.quantity++;
      this.$forceUpdate();
    },

    decrement(detail, id) {
      if (detail.quantity > 1) {
        detail.quantity--;
        this.$forceUpdate();
      }
    },

    delete_Product_Detail(id) {
      for (var i = 0; i < this.details.length; i++) {
        if (id === this.details[i].detail_id) {
          this.details.splice(i, 1);
        }
      }
    },

    Verified_Qty(detail, id) {
      if (isNaN(detail.quantity) || detail.quantity <= 0) {
        detail.quantity = 1;
      }
    }
  }
};
</script>

<style scoped>
.autocomplete {
  position: relative;
  width: 100%;
}
.autocomplete-input {
  width: 100%;
  padding: 10px;
  border: 1px solid #ccc;
  border-radius: 4px;
}
.autocomplete-result-list {
  position: absolute;
  z-index: 1000;
  width: 100%;
  background: #fff;
  border: 1px solid #ccc;
  border-top: none;
  list-style: none;
  padding: 0;
  margin: 0;
}
.autocomplete-result {
  padding: 10px;
  cursor: pointer;
}
.autocomplete-result:hover {
  background: #eee;
}
</style>
