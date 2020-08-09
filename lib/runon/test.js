var test = `Test value: ${5+2}`;

@module function test(aa=2);
var _$ = (__$) => {
    return aa * 5;
}
@endmodule;