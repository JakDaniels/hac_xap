/*
* (C)2015 Simon Annetts of Ateb Ltd - Licensed under LGPL 2.1 or later
*/

#ifndef _DMX485OUT_H_
#define _DMX485OUT_H_

#define PRINT_OPAQUE_STRUCT(p)  print_mem((p), sizeof(*(p)))


static int semaphore_get_access(void);
static int semaphore_release_access(void);
int semaphore_id;
union semun {
	int val;
	struct semid_ds *buf;
	unsigned short *array;
};

typedef struct {
	unsigned char dmx_values[512]; //shared memory representation of 512 dmx channels
} shm_state;

void intHandler(int dummy);
void print_mem(void const *vp, size_t n);
int semaphore_get_access(void);
int semaphore_release_access(void);

#endif /* _DMX485OUT_H_ */