<template>
  <div class="main-content">
    <breadcumb :page="$t('AccountsPayable')" :folder="$t('Finance')"/>

    <div v-if="isLoading" class="loading_page spinner spinner-primary mr-3"></div>
    <b-card class="wrapper" v-if="!isLoading">
      <vue-good-table
        mode="remote"
        :columns="columns"
        :totalRows="payables.length"
        :rows="payables"
        styleClass="table-hover tableOne vgt-table"
      >
        <template slot="table-row" slot-scope="props">
           <span v-if="props.column.field == 'balance'">
            <b-badge variant="danger">{{ formatNumber(props.row.balance, 2) }}</b-badge>
          </span>
        </template>
      </vue-good-table>
    </b-card>
  </div>
</template>

<script>
import NProgress from "nprogress";

export default {
  metaInfo: {
    title: "Accounts Payable"
  },
  data() {
    return {
      isLoading: true,
      payables: [],
      columns: [
        { label: this.$t('Supplier'), field: 'name', tdClass: 'text-left', thClass: 'text-left' },
        { label: this.$t('Phone'), field: 'phone', tdClass: 'text-left', thClass: 'text-left' },
        { label: this.$t('Total_Purchases'), field: 'total_purchases', type: 'decimal', tdClass: 'text-left', thClass: 'text-left' },
        { label: this.$t('Paid'), field: 'paid_amount', type: 'decimal', tdClass: 'text-left', thClass: 'text-left' },
        { label: this.$t('Balance'), field: 'balance', type: 'decimal', tdClass: 'text-left', thClass: 'text-left' },
      ]
    };
  },
  methods: {
    formatNumber(number, dec) {
      const value = (typeof number === "string" ? parseFloat(number) : number).toFixed(dec);
      return value;
    },
    Get_Payables() {
      NProgress.start();
      axios.get("payable_accounts")
        .then(response => {
          this.payables = response.data.payables;
          this.isLoading = false;
          NProgress.done();
        })
        .catch(error => {
          this.isLoading = false;
          NProgress.done();
        });
    }
  },
  created() {
    this.Get_Payables();
  }
};
</script>
