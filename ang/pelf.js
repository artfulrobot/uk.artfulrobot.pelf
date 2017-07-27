(function(angular, $, _) {
  // Declare a list of dependencies.
  angular.module('pelf', [
    'crmUi', 'crmUtil', 'ngRoute'
  ])
  .filter('localeNumber', function() {
    return function(n) {
      return parseFloat(n).toLocaleString();
    };
  });
})(angular, CRM.$, CRM._);
