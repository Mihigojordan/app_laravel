<template>
  <div class="main-content">
    <breadcumb :page="$t('ChartOfAccounts')" :folder="$t('Finance')"/>

    <div v-if="isLoading" class="loading_page spinner spinner-primary mr-3"></div>
    <div v-else>
      <b-row>
        <b-col md="6" v-for="(group, type) in accounts" :key="type" class="mb-4">
          <b-card :title="$t(type)">
            <b-table
              :items="group"
              :fields="fields"
              responsive="sm"
              head-variant="light"
            >
              <template #cell(balance)="data">
                {{ formatNumber(data.value, 2) }}
              </template>
            </b-table>
          </b-card>
        </b-col>
      </b-row>
    </div>
  </div>
</template>

<script>
import NProgress from "nprogress";

export default {
  metaInfo: {
    title: "Chart of Accounts"
  },
  data() {
    return {
      isLoading: true,
      accounts: {},
      fields: [
        { key: 'account_num', label: this.$t('account_num'), sortable: true },
        { key: 'account_name', label: this.$t('account_name'), sortable: true },
        { key: 'balance', label: this.$t('Balance'), sortable: true },
      ]
    };
  },
  methods: {
    formatNumber(number, dec) {
      const value = (typeof number === "string" ? parseFloat(number) : number).toFixed(dec);
      return value;
    },
    Get_Chart_Of_Accounts() {
      NProgress.start();
      axios.get("chart_of_accounts")
        .then(response => {
          this.accounts = response.data.accounts;
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
    this.Get_Chart_Of_Accounts();
  }
};
</script>
