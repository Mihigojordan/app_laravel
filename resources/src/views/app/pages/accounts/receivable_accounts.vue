<template>
  <div class="main-content">
    <breadcumb :page="$t('AccountsReceivable')" :folder="$t('Finance')"/>

    <div v-if="isLoading" class="loading_page spinner spinner-primary mr-3"></div>
    <b-card class="wrapper" v-if="!isLoading">
      <vue-good-table
        mode="remote"
        :columns="columns"
        :totalRows="receivables.length"
        :rows="receivables"
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
    title: "Accounts Receivable"
  },
  data() {
    return {
      isLoading: true,
      receivables: [],
      columns: [
        { label: this.$t('Customer'), field: 'name', tdClass: 'text-left', thClass: 'text-left' },
        { label: this.$t('Phone'), field: 'phone', tdClass: 'text-left', thClass: 'text-left' },
        { label: this.$t('Total_Sales'), field: 'total_sales', type: 'decimal', tdClass: 'text-left', thClass: 'text-left' },
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
    Get_Receivables() {
      NProgress.start();
      axios.get("receivable_accounts")
        .then(response => {
          this.receivables = response.data.receivables;
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
    this.Get_Receivables();
  }
};
</script>
