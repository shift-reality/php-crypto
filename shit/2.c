
#include <string.h>
#include <stdio.h>
#include <stdint.h>




void main( ){

int i;
uint8_t* t8 = (uint8_t*)malloc(16);
uint32_t*t32=(uint32_t*)t8;

//t32[1] = 0xffffffff;
//t32[3] = 0xffffffff;
t8[0]=0xff;

printf("read as 8 bits: ");

for(i = 0; i < 16; ++i)
printf("%d ", t8[i]);

printf("\n\nread as 32 bits: ");

for(i = 0; i < 4; ++i)
printf("%d ", t32[i]);
printf("\n");
} 







